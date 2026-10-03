-- Dr. Subhash University Placement Portal - Full Database Backup & Commitment
-- Generated on 2026-10-03 08:52:11
-- Database: dr_subhash_placement

SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `dr_subhash_placement` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dr_subhash_placement`;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','recruiter') NOT NULL DEFAULT 'student',
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('1', 'admin', '$2y$10$pEfY6LoIykuCiRpEnt.sXe3IzBHrzbH5gB3nAa4y1XeIiMN1s7N0K', 'admin', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('2', 'admin@drsubhash.edu.in', '$2y$10$pEfY6LoIykuCiRpEnt.sXe3IzBHrzbH5gB3nAa4y1XeIiMN1s7N0K', 'admin', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('3', 'hr.google@gmail.com', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'recruiter', '1', '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('4', 'hr.microsoft@gmail.com', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'recruiter', '2', '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('5', 'hr.aws@gmail.com', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'recruiter', '3', '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('6', 'hr.tcs@gmail.com', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'recruiter', '4', '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('7', 'hr.infosys@gmail.com', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'recruiter', '5', '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('8', 'hr.lt@gmail.com', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'recruiter', '6', '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('9', '260130316001', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('10', '260130316002', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('11', '260130316003', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('12', '260130316004', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('13', '260130316005', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('14', '260130316006', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('15', '260130316007', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('16', '260130316008', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('17', '260130316009', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `created_at`) VALUES ('18', '260130316010', '$2y$10$77k8jVLkGN4JBjAoe6aFs./ofMqP3gEA3U3jf0LZFYNjooo0iT6R2', 'student', NULL, '2026-10-03 12:17:31');

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
  `current_semester` int(11) DEFAULT 7,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `offer_letter` varchar(255) DEFAULT NULL,
  `placed_companies` varchar(255) DEFAULT NULL,
  `current_package_lpa` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_no` (`enrollment_no`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('1', '9', '260130316001', 'Aarav Patel', 'aarav.2026@drsubhash.edu.in', '9876543210', 'Male', 'CSE', '9.40', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-10-03 12:17:31', '7', 'https://www.linkedin.com/in/aarav-patel-dsu', 'offer_letter_260130316001.pdf', 'Google India Pvt Ltd (₹24.50 LPA)', NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('2', '10', '260130316002', 'Diya Sharma', 'diya.2026@drsubhash.edu.in', '9876543210', 'Male', 'IT', '8.90', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-10-03 12:17:31', '7', 'https://www.linkedin.com/in/diya-sharma-tech', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('3', '11', '260130316003', 'Kabir Mehta', 'kabir.2026@drsubhash.edu.in', '9876543210', 'Male', 'ECE', '8.50', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-10-03 12:17:31', '7', 'https://www.linkedin.com/in/kabir-mehta-ece', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('4', '12', '260130316004', 'Anaya Joshi', 'anaya.2026@drsubhash.edu.in', '9876543210', 'Male', 'CSE', '9.75', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-10-03 12:17:31', '6', 'https://www.linkedin.com/in/anaya-joshi-ai', 'offer_letter_260130316001.pdf', 'Google India Pvt Ltd (₹24.50 LPA)', NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('5', '13', '260130316005', 'Rudra Shah', 'rudra.2026@drsubhash.edu.in', '9876543210', 'Male', 'MECH', '7.80', '1', '2026', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-10-03 12:17:31', '8', 'https://www.linkedin.com/in/rudra-shah-mech', 'offer_letter_260130316001.pdf', 'Google India Pvt Ltd (₹24.50 LPA)', NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('6', '14', '260130316006', 'Isha Trivedi', 'isha.2026@drsubhash.edu.in', '9876543210', 'Male', 'CIVIL', '8.20', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-10-03 12:17:31', '7', 'https://www.linkedin.com/in/isha-trivedi-civil', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('7', '15', '260130316007', 'Vivaan Kulkarni', 'vivaan.2026@drsubhash.edu.in', '9876543210', 'Male', 'IT', '8.65', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-10-03 12:17:31', '5', 'https://www.linkedin.com/in/vivaan-kulkarni-devops', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('8', '16', '260130316008', 'Riya Desai', 'riya.2026@drsubhash.edu.in', '9876543210', 'Male', 'CSE', '9.10', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-10-03 12:17:31', '7', 'https://www.linkedin.com/in/riya-desai-cloud', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('9', '17', '260130316009', 'Devansh Varma', 'devansh.2026@drsubhash.edu.in', '9876543210', 'Male', 'ECE', '7.40', '2', '2026', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-10-03 12:17:31', '6', 'https://www.linkedin.com/in/devansh-varma-iot', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `current_semester`, `linkedin_url`, `offer_letter`, `placed_companies`, `current_package_lpa`) VALUES ('10', '18', '260130316010', 'Kavya Nair', 'kavya.2026@drsubhash.edu.in', '9876543210', 'Male', 'IT', '8.95', '0', '2026', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-10-03 12:17:31', '8', 'https://www.linkedin.com/in/kavya-nair-fullstack', NULL, 'Goldman Sachs India (₹22.00 LPA - Off-Campus)', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('1', 'Google India Pvt Ltd', 'Sundar Pichai', 'hr.google@gmail.com', 'Cloud & AI Engineering', 'https://careers.google.com', NULL, 'default_company.png', '0', '2026-10-03 12:17:31');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('2', 'Microsoft India', 'Satya Nadella', 'hr.microsoft@gmail.com', 'Enterprise Cloud & OS', 'https://careers.microsoft.com', NULL, 'default_company.png', '0', '2026-10-03 12:17:31');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('3', 'Amazon Web Services (AWS)', 'Andy Jassy', 'hr.aws@gmail.com', 'Cloud Infrastructure & DevOps', 'https://aws.amazon.com', NULL, 'default_company.png', '0', '2026-10-03 12:17:31');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('4', 'Tata Consultancy Services (TCS)', 'K. Krithivasan', 'hr.tcs@gmail.com', 'IT Services & Consulting', 'https://tcs.com', NULL, 'default_company.png', '0', '2026-10-03 12:17:31');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('5', 'Infosys Limited', 'Salil Parekh', 'hr.infosys@gmail.com', 'Digital Transformation', 'https://infosys.com', NULL, 'default_company.png', '0', '2026-10-03 12:17:31');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('6', 'Larsen & Toubro (L&T)', 'S. N. Subrahmanyan', 'hr.lt@gmail.com', 'Engineering & Construction', 'https://larsentoubro.com', NULL, 'default_company.png', '0', '2026-10-03 12:17:31');

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
  `allowed_semesters` varchar(100) DEFAULT '5,6,7,8',
  `package_numeric` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `drives_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`, `allowed_semesters`, `package_numeric`) VALUES ('1', '1', 'Software Engineer 2026 (SDE-1)', 'Software Engineer', '₹24.50 LPA', 'Pan India / Remote', '8.50', 'CSE, IT, ECE', '0', '2026-11-15', '2026-11-01', '18:00', NULL, 'Active', '2026-10-03 12:17:31', '7,8', '24.50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`, `allowed_semesters`, `package_numeric`) VALUES ('2', '2', 'Azure Cloud & AI Developer 2026', 'Cloud Engineer', '₹32.00 LPA', 'Pan India / Remote', '9.00', 'CSE, IT', '0', '2026-11-15', '2026-11-01', '18:00', NULL, 'Active', '2026-10-03 12:17:31', '7,8', '32.00');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`, `allowed_semesters`, `package_numeric`) VALUES ('3', '3', 'DevOps & Systems Architect', 'DevOps Engineer', '₹18.00 LPA', 'Pan India / Remote', '8.00', 'CSE, IT, ECE', '0', '2026-11-15', '2026-11-01', '18:00', NULL, 'Active', '2026-10-03 12:17:31', '6,7,8', '18.00');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`, `allowed_semesters`, `package_numeric`) VALUES ('4', '4', 'TCS Digital & Prime Engineer', 'Digital Engineer', '₹9.00 LPA', 'Pan India / Remote', '7.00', 'CSE, IT, ECE, MECH, CIVIL', '0', '2026-11-15', '2026-11-01', '18:00', NULL, 'Active', '2026-10-03 12:17:31', '5,6,7,8', '9.00');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`, `allowed_semesters`, `package_numeric`) VALUES ('5', '5', 'Specialist Programmer & Systems Engineer', 'System Associate', '₹9.50 LPA', 'Pan India / Remote', '7.50', 'CSE, IT, ECE', '0', '2026-11-15', '2026-11-01', '18:00', NULL, 'Active', '2026-10-03 12:17:31', '6,7,8', '9.50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`, `allowed_semesters`, `package_numeric`) VALUES ('6', '6', 'Graduate Engineer Trainee 2026', 'GET Mechanical/Civil', '₹7.50 LPA', 'Pan India / Remote', '6.50', 'MECH, CIVIL', '0', '2026-11-15', '2026-11-01', '18:00', NULL, 'Active', '2026-10-03 12:17:31', '7,8', '7.50');

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('1', '1', '1', 'Selected', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('2', '1', '2', 'Shortlisted', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('3', '2', '4', 'Selected', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('4', '2', '8', 'Interview Round', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('5', '3', '3', 'Shortlisted', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('6', '4', '7', 'Applied', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('7', '6', '5', 'Selected', NULL, '2026-10-03 12:17:31');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('8', '6', '6', 'Shortlisted', NULL, '2026-10-03 12:17:31');

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
  `company_id` int(11) DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `workshops` (`id`, `title`, `speaker`, `event_date`, `venue`, `capacity`, `description`, `status`, `created_at`, `company_id`, `report_file`) VALUES ('1', 'AI & Generative LLM Architecture Masterclass', 'Dr. Sundar Pichai & Google Engineers', '2026-10-25 10:00:00', 'Main Auditorium', '100', NULL, 'Upcoming', '2026-10-03 12:17:31', '1', NULL);
INSERT INTO `workshops` (`id`, `title`, `speaker`, `event_date`, `venue`, `capacity`, `description`, `status`, `created_at`, `company_id`, `report_file`) VALUES ('2', 'Full-Stack Cloud Microservices & DevOps Bootcamp', 'Satya Nadella & Azure Architects', '2026-10-25 10:00:00', 'CS Lab 3', '100', NULL, 'Upcoming', '2026-10-03 12:17:31', '2', NULL);
INSERT INTO `workshops` (`id`, `title`, `speaker`, `event_date`, `venue`, `capacity`, `description`, `status`, `created_at`, `company_id`, `report_file`) VALUES ('3', 'Cybersecurity & Ethical Hacking Intensive', 'AWS Security Team', '2026-10-25 10:00:00', 'Seminar Hall B', '100', NULL, 'Completed', '2026-10-03 12:17:31', '3', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `verification_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `credit_points` int(11) DEFAULT 3,
  `faculty_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verification_remarks` text DEFAULT NULL,
  `credits_awarded` decimal(3,1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `internships_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `internships` (`id`, `student_id`, `company_name`, `title`, `stipend`, `start_date`, `end_date`, `certificate_file`, `status`, `created_at`, `verification_status`, `credit_points`, `faculty_remarks`, `verified_by`, `verification_remarks`, `credits_awarded`) VALUES ('1', '1', 'Google India', 'Cloud Developer Intern', '₹45,000 / month', '2026-05-01', '2026-07-31', 'certificate_sample.pdf', 'Completed', '2026-10-03 12:17:31', 'Verified', '4', 'Excellent performance verified with company certificate seal', NULL, NULL, NULL);
INSERT INTO `internships` (`id`, `student_id`, `company_name`, `title`, `stipend`, `start_date`, `end_date`, `certificate_file`, `status`, `created_at`, `verification_status`, `credit_points`, `faculty_remarks`, `verified_by`, `verification_remarks`, `credits_awarded`) VALUES ('2', '4', 'Microsoft India', 'Software Systems Intern', '₹50,000 / month', '2026-05-01', '2026-07-31', 'certificate_sample.pdf', 'Completed', '2026-10-03 12:17:31', 'Verified', '6', 'Major semester project credit approved', NULL, NULL, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `mous` (`id`, `company_name`, `signed_date`, `expiry_date`, `scope`, `status`, `created_at`) VALUES ('1', 'Google India Pvt Ltd', '2024-01-15', '2027-01-15', 'Cloud & AI Student Excellence Center and Direct Campus Hiring', 'Active', '2026-10-03 12:17:31');
INSERT INTO `mous` (`id`, `company_name`, `signed_date`, `expiry_date`, `scope`, `status`, `created_at`) VALUES ('2', 'Microsoft India', '2024-03-01', '2027-03-01', 'Azure Cloud Academy & Software Developer Fellowships', 'Active', '2026-10-03 12:17:31');
INSERT INTO `mous` (`id`, `company_name`, `signed_date`, `expiry_date`, `scope`, `status`, `created_at`) VALUES ('3', 'Amazon Web Services (AWS)', '2023-08-10', '2026-08-10', 'AWS Cloud Infrastructure Lab Sponsoring', 'Active', '2026-10-03 12:17:31');
INSERT INTO `mous` (`id`, `company_name`, `signed_date`, `expiry_date`, `scope`, `status`, `created_at`) VALUES ('4', 'Tata Consultancy Services (TCS)', '2025-02-01', '2028-02-01', 'Industry Curriculum Integration & Internship Pool', 'Active', '2026-10-03 12:17:31');

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

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES ('allow_higher_package_reapply', '0', '2026-10-03 12:20:30');
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES ('higher_package_min_increment', '30', '2026-10-03 12:20:30');
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES ('higher_package_min_multiplier', '1.30', '2026-10-03 12:17:31');
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES ('higher_package_policy_enabled', '1', '2026-10-03 12:17:31');

SET FOREIGN_KEY_CHECKS = 1;
