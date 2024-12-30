-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 30, 2024 at 01:14 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `metal_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `metal_type` enum('Gold','Silver','Platinum') DEFAULT NULL,
  `sr_no` varchar(10) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `alt_mobile` varchar(15) DEFAULT NULL,
  `sample` varchar(50) DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `metal_type`, `sr_no`, `report_date`, `name`, `mobile`, `alt_mobile`, `sample`, `weight`) VALUES
(1, 'Gold', 'K 1', '2024-11-29', 'Fredrick Pages', '+9164646454454', NULL, 'K', 0.500),
(2, 'Silver', 'L 1', '2024-12-07', 'GHA', '+9134534534543', NULL, '5J', 0.700),
(10, 'Platinum', 'L 2', '2024-12-07', 'RWW', '+915555252254', NULL, 'asd', 0.630),
(11, 'Silver', 'L 3', '2024-12-07', 'A6A', '+913236552563', NULL, 'K4', 0.300);

-- --------------------------------------------------------

--
-- Table structure for table `test_reports`
--

CREATE TABLE `test_reports` (
  `id` int(11) NOT NULL,
  `sr_no` varchar(10) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sample` varchar(50) DEFAULT NULL,
  `metal_type` enum('Gold','Silver','Platinum') DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `alt_mobile` varchar(15) DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL,
  `gold_percent` decimal(5,2) DEFAULT 0.00,
  `silver` decimal(5,2) DEFAULT 0.00,
  `zinc` decimal(5,2) DEFAULT 0.00,
  `copper` decimal(5,2) DEFAULT 0.00,
  `others` decimal(5,2) DEFAULT 0.00,
  `platinum` decimal(5,2) DEFAULT 0.00,
  `rhodium` decimal(5,2) DEFAULT 0.00,
  `iridium` decimal(5,2) DEFAULT 0.00,
  `ruthenium` decimal(5,2) DEFAULT 0.00,
  `palladium` decimal(5,2) DEFAULT 0.00,
  `lead` decimal(5,2) DEFAULT 0.00,
  `total_karat` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_reports`
--

INSERT INTO `test_reports` (`id`, `sr_no`, `report_date`, `name`, `sample`, `metal_type`, `count`, `mobile`, `alt_mobile`, `weight`, `gold_percent`, `silver`, `zinc`, `copper`, `others`, `platinum`, `rhodium`, `iridium`, `ruthenium`, `palladium`, `lead`, `total_karat`) VALUES
(12, 'K 1', '2024-12-07', 'Fredrick Pages', 'K', 'Gold', 0, '+9164646454454', NULL, 0.500, 91.34, 3.00, 7.00, 8.00, 9.00, 2.00, 8.00, 9.00, 78.00, 9.00, 8.00, 21.92),
(13, 'L 3', '2024-12-07', 'A6A', 'K4', 'Silver', 1, '+913236552563', NULL, 0.300, 91.75, 2.00, 0.00, 5.00, 5.00, 5.00, 5.00, 5.00, 5.00, 5.00, 5.00, 22.02),
(14, 'L 9', '2024-12-23', 'Jacob P', 'Ring', 'Gold', 0, '+919393939393', NULL, 0.300, 91.75, 2.00, 4.00, 5.00, 6.00, 3.00, 7.00, 8.00, 9.00, 7.00, 1.00, 22.02),
(15, 'L 9', '2024-12-23', 'Jacob P', 'Ring', 'Gold', 0, '+919393939393', NULL, 0.300, 91.75, 2.00, 4.00, 5.00, 6.00, 3.00, 7.00, 8.00, 9.00, 7.00, 1.00, 22.02),
(16, 'L 9', '2024-12-23', 'Jacob P', 'Ring', 'Gold', 2, '+919393939393', NULL, 0.300, 91.75, 9.00, 0.00, 9.00, 7.00, 8.00, 6.00, 7.00, 8.00, 9.00, 0.00, 22.02);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `test_reports`
--
ALTER TABLE `test_reports`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `test_reports`
--
ALTER TABLE `test_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
