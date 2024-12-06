-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2024 at 12:47 PM
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
(7, 'Platinum', '100', '2024-12-03', 'GG Platinum', '101010101010', 'K', 7.000);

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
(11, '100', '2024-12-06', 'GG Platinum', 'K', 'Platinum', 0, '101010101010', 7.000, 85.20, 55.00, 5.00, 5.00, 5.00, 5.00, 0.00, 2.00, 6.00, 4.00, 3.00, 20.45);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `test_reports`
--
ALTER TABLE `test_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
