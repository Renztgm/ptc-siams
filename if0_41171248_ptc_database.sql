-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql302.infinityfree.com
-- Generation Time: Feb 18, 2026 at 03:54 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41171248_ptc_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `Admin_account`
--

CREATE TABLE `Admin_account` (
  `id` int(11) NOT NULL,
  `adminid` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Admin_account`
--

INSERT INTO `Admin_account` (`id`, `adminid`, `username`, `password`) VALUES
(1, '2026-00007', 'admin', 'password');

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int(11) NOT NULL,
  `admission_id` varchar(20) NOT NULL,
  `given_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `program` varchar(200) NOT NULL,
  `submission_date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `admission_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_sent_date` datetime DEFAULT NULL,
  `exam_link_sent` tinyint(1) DEFAULT 0,
  `exam_link_sent_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_stats`
--

CREATE TABLE `admission_stats` (
  `id` int(11) NOT NULL,
  `stat_date` date DEFAULT curdate(),
  `program` varchar(200) DEFAULT NULL,
  `total_applications` int(11) DEFAULT 0,
  `admitted` int(11) DEFAULT 0,
  `rejected` int(11) DEFAULT 0,
  `registered_for_exam` int(11) DEFAULT 0,
  `exam_completed` int(11) DEFAULT 0,
  `passed` int(11) DEFAULT 0,
  `failed` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(100) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `admission_id` int(11) DEFAULT NULL,
  `email_type` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `sent_date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_registrations`
--

CREATE TABLE `exam_registrations` (
  `id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `exam_session_id` int(11) NOT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `attendance_status` varchar(50) DEFAULT NULL,
  `attendance_time` datetime DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `score_percentage` decimal(5,2) DEFAULT NULL,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `result_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'registered',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_sessions`
--

CREATE TABLE `exam_sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `exam_date` date NOT NULL,
  `exam_start_time` time NOT NULL,
  `exam_end_time` time NOT NULL,
  `exam_format` varchar(50) DEFAULT NULL,
  `exam_location` varchar(255) DEFAULT NULL,
  `exam_link` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Faculty_account`
--

CREATE TABLE `Faculty_account` (
  `id` int(11) NOT NULL,
  `facultyid` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Faculty_account`
--

INSERT INTO `Faculty_account` (`id`, `facultyid`, `username`, `password`) VALUES
(1, 'faculty-000026', 'facultyadmin', 'ptc_arquero');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(200) NOT NULL,
  `program_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `total_slots` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `program_code`, `description`, `total_slots`, `created_at`) VALUES
(1, 'BS Information Technology', 'BSIT', NULL, NULL, '2026-02-18 08:00:47'),
(2, 'BS Business Administration', 'BSBA', NULL, NULL, '2026-02-18 08:00:47'),
(3, 'BS Hospitality Management', 'BSHM', NULL, NULL, '2026-02-18 08:00:47'),
(4, 'BS Nursing', 'BSN', NULL, NULL, '2026-02-18 08:00:47'),
(5, 'BS Criminology', 'BSCRIM', NULL, NULL, '2026-02-18 08:00:47'),
(6, 'Associate in Hotel and Restaurant Management', 'AHRM', NULL, NULL, '2026-02-18 08:00:47'),
(7, 'Associate in Office Administration', 'AOA', NULL, NULL, '2026-02-18 08:00:47'),
(8, 'Associate in Education', 'AED', NULL, NULL, '2026-02-18 08:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `Stud_account`
--

CREATE TABLE `Stud_account` (
  `id` int(11) NOT NULL,
  `studentid` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Stud_account`
--

INSERT INTO `Stud_account` (`id`, `studentid`, `password`, `username`) VALUES
(1, 'admin@2026', 'admin@!!', 'admin'),
(2, '2345', 'ganda', 'test1'),
(3, '22-00007', 'Code#12345', '22-00007'),
(7, 'password', 'password', 'password'),
(8, 'admin01', 'admin123', 'admin01\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `actor` varchar(100) DEFAULT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `log_date` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Admin_account`
--
ALTER TABLE `Admin_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_id` (`admission_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_program` (`program`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_admission_id` (`admission_id`);

--
-- Indexes for table `admission_stats`
--
ALTER TABLE `admission_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stat` (`stat_date`,`program`),
  ADD KEY `idx_program` (`program`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admission_id` (`admission_id`),
  ADD KEY `idx_sent_date` (`sent_date`),
  ADD KEY `idx_email_type` (`email_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `exam_registrations`
--
ALTER TABLE `exam_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`admission_id`,`exam_session_id`),
  ADD KEY `idx_admission_id` (`admission_id`),
  ADD KEY `idx_exam_session_id` (`exam_session_id`),
  ADD KEY `idx_result` (`result`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_date` (`exam_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `Faculty_account`
--
ALTER TABLE `Faculty_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_name` (`program_name`),
  ADD KEY `idx_program_name` (`program_name`);

--
-- Indexes for table `Stud_account`
--
ALTER TABLE `Stud_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_log_date` (`log_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Admin_account`
--
ALTER TABLE `Admin_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_stats`
--
ALTER TABLE `admission_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_registrations`
--
ALTER TABLE `exam_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Faculty_account`
--
ALTER TABLE `Faculty_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `Stud_account`
--
ALTER TABLE `Stud_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
