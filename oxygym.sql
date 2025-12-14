-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 07:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oxygym`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `Member_ID` int(11) NOT NULL,
  `Address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`Member_ID`, `Address`) VALUES
(19, 'jgghf');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `Attendance_ID` int(11) NOT NULL,
  `Member_ID` int(11) NOT NULL,
  `Check_In` datetime DEFAULT current_timestamp(),
  `Check_Out` datetime DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `Member_ID` int(11) NOT NULL,
  `First_Name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` int(11) NOT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Birthdate` date DEFAULT NULL,
  `Join_Date` date DEFAULT NULL,
  `Membership_ID` int(11) DEFAULT NULL,
  `Expiry_Date` date DEFAULT NULL,
  `STATUS` enum('Active','Expired','Pending') DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`Member_ID`, `First_Name`, `Last_Name`, `Email`, `Phone`, `Address`, `Gender`, `Birthdate`, `Join_Date`, `Membership_ID`, `Expiry_Date`, `STATUS`, `Created_At`, `Updated_At`) VALUES
(9, 'Jairus', 'Segovia', 'jai@gmail.com', NULL, 0, 'Male', '2006-01-26', '2025-11-26', NULL, NULL, 'Active', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(10, 'JD', 'KAlisag', 'JD@gmail.com', NULL, 0, NULL, NULL, '2025-11-27', NULL, NULL, 'Active', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(11, 'JD', 'KAlisag', 'J_d@gmail.com', NULL, 0, 'Male', '2012-01-27', '2025-11-27', NULL, NULL, 'Active', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(12, 'yut', 'iyu', 'yut@gmail.com', NULL, 0, NULL, NULL, '2025-11-27', NULL, NULL, 'Active', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(13, 'Test', 'User', 'test@example.com', NULL, 0, NULL, NULL, '2025-11-29', NULL, NULL, 'Active', '2025-11-29 08:23:37', '2025-11-29 08:23:37'),
(14, 'daw', 'daw', 'ddd@gmail.com', NULL, 0, NULL, NULL, '2025-11-29', NULL, NULL, 'Active', '2025-11-29 12:42:34', '2025-11-29 12:42:34'),
(15, 'Cabs', 'daw', 'cab@gmail.com', '423', 0, 'Male', '2004-06-09', '2025-11-30', NULL, NULL, 'Active', '2025-11-30 06:29:06', '2025-12-11 19:02:01'),
(16, 'Jude', 'Segovia', 'jude@gmail.com', NULL, 0, NULL, NULL, '2025-12-04', NULL, NULL, 'Active', '2025-12-04 07:19:01', '2025-12-04 07:19:01'),
(17, 'Jude', 'Segovia', 'jule@gmail.com', NULL, 0, NULL, NULL, '2025-12-04', NULL, NULL, 'Active', '2025-12-04 07:19:10', '2025-12-04 07:19:10'),
(18, 'Jude', 'Salazar', 'Salazar123@gmail.com', NULL, 0, NULL, NULL, '2025-12-04', NULL, NULL, 'Active', '2025-12-04 07:19:48', '2025-12-04 07:19:48'),
(19, 'jude', 'salazar', 'Salazar@gmail.com', 'a243', 0, 'Female', '2006-01-12', '2025-12-04', NULL, NULL, 'Active', '2025-12-04 07:20:59', '2025-12-08 14:11:11'),
(20, 'cabs', 'tester', 'test@gmail.com', NULL, 0, NULL, NULL, '2025-12-11', NULL, NULL, 'Active', '2025-12-10 16:14:33', '2025-12-10 16:14:33'),
(21, 'rwrw', 'wr3', 'qw@gmail.com', '423', 0, 'Male', '2025-12-10', '2025-12-12', NULL, NULL, 'Active', '2025-12-12 05:18:10', '2025-12-12 05:18:37'),
(22, 'daw', 'Segovia', 'dw@gmail.com', NULL, 0, NULL, NULL, '2025-12-14', NULL, NULL, 'Active', '2025-12-14 05:47:38', '2025-12-14 05:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `membership_types`
--

CREATE TABLE `membership_types` (
  `Membership_ID` int(11) NOT NULL,
  `NAME` varchar(100) DEFAULT NULL,
  `Duration_Days` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_types`
--

INSERT INTO `membership_types` (`Membership_ID`, `NAME`, `Duration_Days`, `Price`, `Description`) VALUES
(1, 'Standard', 30, 999.00, 'Basic gym access'),
(2, 'Prime', 30, 1499.00, '1-on-1 coaching + nutrition'),
(3, 'Premium', 365, 14999.00, 'All prime benefits + exclusive events');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `Review_ID` int(11) NOT NULL,
  `Member_ID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Body` text DEFAULT NULL,
  `Created_At` datetime DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`Review_ID`, `Member_ID`, `Rating`, `Title`, `Body`, `Created_At`, `Updated_At`) VALUES
(1, 15, 4, 'aw', 'wadawd', '2025-12-12 02:59:45', '2025-12-11 18:59:45'),
(2, 21, 3, 'rw3', 'rw3', '2025-12-12 13:18:58', '2025-12-12 05:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `Service_ID` int(11) NOT NULL,
  `NAME` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_history`
--

CREATE TABLE `subscription_history` (
  `Subscription_ID` int(11) NOT NULL,
  `Member_ID` int(11) NOT NULL,
  `Membership_ID` int(11) NOT NULL,
  `Start_Date` date NOT NULL,
  `End_Date` date NOT NULL,
  `STATUS` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `Transaction_ID` int(11) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_history`
--

INSERT INTO `subscription_history` (`Subscription_ID`, `Member_ID`, `Membership_ID`, `Start_Date`, `End_Date`, `STATUS`, `Transaction_ID`, `Created_At`, `Updated_At`) VALUES
(6, 9, 3, '2025-11-27', '2025-12-27', 'Active', NULL, '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(7, 11, 1, '2025-11-27', '2025-12-27', 'Active', NULL, '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(8, 15, 3, '2025-11-30', '2025-12-30', 'Expired', NULL, '2025-11-30 06:50:54', '2025-12-11 19:02:01'),
(9, 15, 2, '2025-11-30', '2025-12-30', 'Expired', NULL, '2025-11-30 06:53:04', '2025-12-11 19:02:01'),
(10, 19, 1, '2025-12-04', '2026-01-03', 'Active', NULL, '2025-12-04 07:22:07', '2025-12-04 07:22:07'),
(11, 19, 3, '2025-12-08', '2026-01-07', 'Active', NULL, '2025-12-08 13:50:53', '2025-12-08 13:50:53'),
(12, 19, 3, '2025-12-08', '2026-01-07', 'Active', NULL, '2025-12-08 14:00:05', '2025-12-08 14:00:05'),
(13, 19, 3, '2025-12-08', '2026-01-07', 'Active', NULL, '2025-12-08 14:00:18', '2025-12-08 14:00:18'),
(14, 19, 1, '2025-12-08', '2026-01-07', 'Active', NULL, '2025-12-08 14:11:11', '2025-12-08 14:11:11'),
(15, 19, 1, '2025-12-08', '2026-01-07', 'Active', NULL, '2025-12-08 14:11:17', '2025-12-08 14:11:17'),
(16, 15, 2, '2025-12-11', '2026-01-10', 'Expired', NULL, '2025-12-11 19:02:01', '2025-12-11 19:02:09'),
(17, 15, 3, '2025-12-11', '2026-01-10', 'Active', NULL, '2025-12-11 19:02:09', '2025-12-11 19:02:09'),
(18, 21, 3, '2025-12-12', '2026-01-11', 'Expired', NULL, '2025-12-12 05:18:37', '2025-12-12 05:19:14'),
(19, 21, 2, '2025-12-12', '2026-01-11', 'Expired', NULL, '2025-12-12 05:19:14', '2025-12-12 05:19:31'),
(20, 21, 3, '2025-12-12', '2026-01-11', 'Active', NULL, '2025-12-12 05:19:31', '2025-12-12 05:19:31');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `Transaction_ID` int(11) NOT NULL,
  `Member_ID` int(11) NOT NULL,
  `DATE` datetime DEFAULT current_timestamp(),
  `Payment_Method` enum('GCash','Cash','Card') NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Reference_No` varchar(100) DEFAULT NULL,
  `STATUS` enum('Paid','Pending') DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `Member_ID` int(11) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `Password_Hash` varchar(255) NOT NULL,
  `Role` enum('Admin','Staff','Member') NOT NULL DEFAULT 'Member',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `Member_ID`, `Username`, `Password_Hash`, `Role`, `Created_At`, `Updated_At`) VALUES
(9, 9, 'jai@gmail.com', '$2y$10$fu4VQsWn42Hk7jij/2lwWe.e/svme4BbtwL9IAxP3vWd75gFDn.tK', 'Admin', '2025-11-29 06:59:42', '2025-11-30 06:26:13'),
(10, 10, 'JD', '$2y$10$aIRXrji9FmILDvIqdDjRC.ZnynI5WlbpY3IbgGKtkYt1rzZLQD2Ke', 'Member', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(11, 11, 'J_d', '$2y$10$v57D19zVcIJH5/I3SLUukuMU5yl6izctDCSoIYHqO20VuAznaxqAy', 'Member', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(12, 12, 'yut', '$2y$10$2HhsF0n.D1KRIAIxjeYzYOV/EiHIZ87k/Tq/S5t0R.L7sI2iXyKx.', 'Member', '2025-11-29 06:59:42', '2025-11-29 06:59:42'),
(13, NULL, 'admin', '$2y$10$Hn/DqcNyQNKSIoyr.EfHz.DtYZd.YIgW8grHx/bujVkZoyqHPTglq', 'Admin', '2025-11-29 06:59:42', '2025-11-29 08:12:40'),
(14, 13, 'testuser@gmail.com', '$2y$10$jCBp3t1fo4.4eF2s6CdFWONIXp4nmx49pBl3L7FcoL.RMy2EJyBQm', 'Staff', '2025-11-29 08:23:37', '2025-12-10 16:12:17'),
(15, 14, 'ddd@gmail.com', '$2y$10$o4RG3OPGHouUjhN46ihqLuFrBEZnDWS3lKGebD5KRAVQzOoXaHIxW', 'Member', '2025-11-29 12:42:34', '2025-11-29 12:42:34'),
(16, 15, 'cab@gmail.com', '$2y$10$rxaNnRBs/MwzvXGpY4T5V.8FYEaWfpi9Wx287bPWyQrcECO4iU3EW', 'Member', '2025-11-30 06:29:06', '2025-11-30 06:29:06'),
(17, 16, 'jude@gmail.com', '$2y$10$EuZbSVrbxsMQVldxjRk3g.zgfASOBwCPnrq3KeH69VpbqUh1gv3ei', 'Member', '2025-12-04 07:19:01', '2025-12-04 07:19:01'),
(18, 17, 'jule@gmail.com', '$2y$10$AU6zZJCshO8eseheqq.OiulL7YDgAPmoZfi4vYkojqhd84DycG3Ja', 'Member', '2025-12-04 07:19:10', '2025-12-04 07:19:10'),
(19, 18, 'Salazar123@gmail.com', '$2y$10$SERWH0inbVly6hAn9JpTgu46G5Llg9CmC0qXcL1O3u4PUU6pszAWa', 'Member', '2025-12-04 07:19:48', '2025-12-04 07:19:48'),
(20, 19, 'Salazar@gmail.com', '$2y$10$OaDLCiApRUNLtTTYWMaI4.EgmR1cQ1aGnsFA8.o39IuSPsNyHsorW', 'Member', '2025-12-04 07:20:59', '2025-12-04 07:20:59'),
(21, 20, 'test@gmail.com', '$2y$10$7Pju0q6fI68e.tfZeFphFermSE2/gJeQsqJoxl/Vtp2ieVclA0k2i', 'Staff', '2025-12-10 16:14:33', '2025-12-10 16:14:57'),
(22, 21, 'qw@gmail.com', '$2y$10$MgTNCQu/QMsTWPKm/4vSrujTc6dSTEeZdCzHWETVg.2yRduFVg5Wm', 'Member', '2025-12-12 05:18:10', '2025-12-12 05:18:10'),
(23, 22, 'dw@gmail.com', '$2y$10$nMZ7BrJzXzBvWFVEEIzhbeJPdAk5y.6ZipGA9317GkBA1n7hP8Exy', 'Member', '2025-12-14 05:47:38', '2025-12-14 05:47:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`Member_ID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`Attendance_ID`),
  ADD KEY `fk_att_member` (`Member_ID`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`Member_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `fk_membership_id` (`Membership_ID`),
  ADD KEY `address` (`Address`),
  ADD KEY `address_2` (`Address`),
  ADD KEY `address_3` (`Address`);

--
-- Indexes for table `membership_types`
--
ALTER TABLE `membership_types`
  ADD PRIMARY KEY (`Membership_ID`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`Review_ID`),
  ADD KEY `idx_review_member` (`Member_ID`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`Service_ID`);

--
-- Indexes for table `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD PRIMARY KEY (`Subscription_ID`),
  ADD KEY `fk_sub_membership` (`Membership_ID`),
  ADD KEY `fk_sub_transaction` (`Transaction_ID`),
  ADD KEY `idx_subscription_member` (`Member_ID`),
  ADD KEY `idx_subscription_status` (`STATUS`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`Transaction_ID`),
  ADD KEY `idx_transaction_member` (`Member_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `idx_user_member` (`Member_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `Attendance_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `Member_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `membership_types`
--
ALTER TABLE `membership_types`
  MODIFY `Membership_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `Service_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_history`
--
ALTER TABLE `subscription_history`
  MODIFY `Subscription_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `Transaction_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `fk_address_member` FOREIGN KEY (`Member_ID`) REFERENCES `members` (`Member_ID`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_member` FOREIGN KEY (`Member_ID`) REFERENCES `members` (`Member_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_membership_id` FOREIGN KEY (`Membership_ID`) REFERENCES `membership_types` (`Membership_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_rev_member` FOREIGN KEY (`Member_ID`) REFERENCES `members` (`Member_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD CONSTRAINT `fk_sub_member` FOREIGN KEY (`Member_ID`) REFERENCES `members` (`Member_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_membership` FOREIGN KEY (`Membership_ID`) REFERENCES `membership_types` (`Membership_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_transaction` FOREIGN KEY (`Transaction_ID`) REFERENCES `transactions` (`Transaction_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_member` FOREIGN KEY (`Member_ID`) REFERENCES `members` (`Member_ID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
