-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2024 at 08:32 AM
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
  `sample` varchar(50) DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `metal_type`, `sr_no`, `report_date`, `name`, `mobile`, `sample`, `weight`) VALUES
(1, 'Gold', 'K 1', '2024-11-29', 'Fredrick Pages', '+9164646454454', 'K', 0.500),
(2, 'Silver', 'L 1', '2024-12-07', 'GHA', '+9134534534543', '5J', 0.700),
(10, 'Platinum', 'L 2', '2024-12-07', 'RWW', '+915555252254', 'asd', 0.630),
(11, 'Silver', 'L 3', '2024-12-07', 'A6A', '+913236552563', 'K4', 0.300),
(12, 'Platinum', 'L 4', '2024-12-07', 'POP', '+913384', '221', 0.360),
(13, 'Platinum', 'L 5', '2024-12-07', 'Arr', '+916695545245', 'K4', 0.300),
(14, 'Silver', 'L 6', '2024-12-07', 'As3', '+916656565656', 'aA', 0.321),
(15, 'Silver', 'L 7', '2024-12-07', 'BCDEQ', '+9123232323', 'LA', 0.150),
(16, 'Platinum', 'L 8', '2024-12-07', 'QWEQ', '+913232323', 'A2', 0.440);

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
  `weight` decimal(10,3) DEFAULT NULL,
  `gold_percent` decimal(5,2) DEFAULT NULL,
  `silver` decimal(5,2) DEFAULT NULL,
  `zinc` decimal(5,2) DEFAULT NULL,
  `copper` decimal(5,2) DEFAULT NULL,
  `others` decimal(5,2) DEFAULT NULL,
  `platinum` decimal(5,2) DEFAULT NULL,
  `rhodium` decimal(5,2) DEFAULT NULL,
  `iridium` decimal(5,2) DEFAULT NULL,
  `ruthenium` decimal(5,2) DEFAULT NULL,
  `palladium` decimal(5,2) DEFAULT NULL,
  `lead` decimal(5,2) DEFAULT NULL,
  `total_karat` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_reports`
--

INSERT INTO `test_reports` (`id`, `sr_no`, `report_date`, `name`, `sample`, `metal_type`, `count`, `mobile`, `weight`, `gold_percent`, `silver`, `zinc`, `copper`, `others`, `platinum`, `rhodium`, `iridium`, `ruthenium`, `palladium`, `lead`, `total_karat`) VALUES
(12, 'K 1', '2024-12-07', 'Fredrick Pages', 'K', 'Gold', 0, '+9164646454454', 0.500, 91.34, 3.00, 7.00, 8.00, 9.00, 2.00, 8.00, 9.00, 78.00, 9.00, 8.00, 21.92),
(13, 'L 3', '2024-12-07', 'A6A', 'K4', 'Silver', 1, '+913236552563', 0.300, 91.75, 2.00, 0.00, 5.00, 5.00, 5.00, 5.00, 5.00, 5.00, 5.00, 5.00, 22.02);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `test_reports`
--
ALTER TABLE `test_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
