-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 05:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `duty_log_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `duties`
--

CREATE TABLE `duties` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `duty_type` varchar(100) NOT NULL,
  `required_hours` int(11) NOT NULL,
  `deadline` date DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `status` enum('assigned','in_progress','completed') DEFAULT 'assigned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duties`
--

INSERT INTO `duties` (`id`, `student_id`, `duty_type`, `required_hours`, `deadline`, `assigned_by`, `status`, `created_at`) VALUES
(1, 7, 'ID Station', 90, NULL, 9, 'assigned', '2025-09-04 13:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `duty_entries`
--

CREATE TABLE `duty_entries` (
  `id` int(11) NOT NULL,
  `duty_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `task_description` text NOT NULL,
  `date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `instructor_feedback` text DEFAULT NULL,
  `signature_data` longblob DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duty_entries`
--

INSERT INTO `duty_entries` (`id`, `duty_id`, `student_id`, `hours`, `task_description`, `date`, `status`, `instructor_feedback`, `signature_data`, `approval_date`, `created_at`) VALUES
(1, 1, 7, 8.00, 'I\'m serving New students to get their ID from 8am to 5pm', '2025-09-03', 'approved', '', NULL, '2025-09-04 14:28:51', '2025-09-04 13:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `overall_performance` enum('excellent','good','satisfactory','needs_improvement') NOT NULL,
  `remarks` text DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `role` enum('student','instructor','scholarship_officer','superadmin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `department`, `role`, `created_at`) VALUES
(7, 'student1', '$2y$10$adndp/fK8MPQAihl4Q6ThenQaDopIG0EwIyT2cKl8uvRPDOtGM96u', 'student1@example.com', 'Engineering', 'student', '2025-09-04 13:28:40'),
(8, 'instructor1', '$2y$10$rseGl.nSEyuH5bQKM0MfJuac2j902mGzVEiUNgZdLPjm6uu6Pi5cC', 'instructor1@example.com', 'Business', 'instructor', '2025-09-04 13:28:40'),
(9, 'officer1', '$2y$10$emehP7Vl0e31hcBrct761O9e52B8uVidJ9vrCJkonApN7JqPpp22e', 'officer1@example.com', 'Education', 'scholarship_officer', '2025-09-04 13:28:40'),
(10, 'admin', '$2y$10$1JeUd64WBF7G1SyxuaJMquvoq4U/HSP.8wnNTSlm/2ao5G8gfwb.K', 'admin@example.com', 'Health Sciences', 'superadmin', '2025-09-04 13:28:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `duties`
--
ALTER TABLE `duties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `duty_entries`
--
ALTER TABLE `duty_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `duty_id` (`duty_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `duties`
--
ALTER TABLE `duties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `duty_entries`
--
ALTER TABLE `duty_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `duties`
--
ALTER TABLE `duties`
  ADD CONSTRAINT `duties_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `duties_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `duty_entries`
--
ALTER TABLE `duty_entries`
  ADD CONSTRAINT `duty_entries_ibfk_1` FOREIGN KEY (`duty_id`) REFERENCES `duties` (`id`),
  ADD CONSTRAINT `duty_entries_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
