-- MySQL dump for Online Routing System
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `online_routing_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `online_routing_system`;

-- Table structure for `colleges`
CREATE TABLE `colleges` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `colleges` (`id`, `name`, `code`) VALUES
(1, 'College of Business Administration', 'CBA'),
(2, 'College of Arts and Sciences', 'CAS'),
(3, 'College of Accounting and Finance', 'CAF'),
(4, 'College of Criminal Justice Education', 'CCJE'),
(5, 'College of Teacher Education', 'CTED');

-- Table structure for `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `college_id` int(11) DEFAULT NULL,
  `role` enum('admin','technician','requester') NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `technicians`
CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `availability` tinyint(1) NOT NULL DEFAULT 1,
  `current_task_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `requests`
CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `college_id` int(11) NOT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('pending','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `assigned_technician_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `task_logs`
CREATE TABLE `task_logs` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `action` enum('assigned','accepted','declined','started','completed','cancelled') NOT NULL,
  `notes` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `feedback`
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for tables
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `college_id` (`college_id`);

ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `current_task_id` (`current_task_id`);

ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `assigned_technician_id` (`assigned_technician_id`);

ALTER TABLE `task_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `technician_id` (`technician_id`);

ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `requester_id` (`requester_id`);

-- AUTO_INCREMENT for tables
ALTER TABLE `colleges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `task_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Constraints for tables
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`);

ALTER TABLE `technicians`
  ADD CONSTRAINT `technicians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`),
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`assigned_technician_id`) REFERENCES `technicians` (`id`);

ALTER TABLE `task_logs`
  ADD CONSTRAINT `task_logs_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
  ADD CONSTRAINT `task_logs_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`);

ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`);
COMMIT;