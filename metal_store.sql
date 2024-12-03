-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2024 at 12:06 PM
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
(1, 'Gold', '123', '2024-11-29', 'ASD', '123456', 'a', 123.000),
(2, 'Silver', '12', '2024-11-22', 'test', '12545', '12', 324.000),
(3, 'Gold', '12', '0000-00-00', 'ggg', '21312', 'agg', 1.000),
(4, 'Silver', '69', '2024-11-28', 'FGGGG', '56969696969', 'ass', 2.000),
(5, 'Gold', '70', '2024-11-28', 'GGbro', '6956589856', 'K', 0.580),
(6, 'Gold', '90', '2024-12-03', 'gg broi', '9888989876', 'gh', 8.000),
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
(1, '123', '2024-11-28', 'ASD', 'a', 'Gold', 0, '123456', 123.000, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL),
(2, '123', '2024-11-28', 'ASD', 'a', 'Gold', 0, '123456', 123.000, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL),
(3, '123', '2024-11-28', 'ASD', 'a', 'Gold', 1232, '123456', 123.000, 12.00, 999.99, 123.00, 123.00, 23.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL),
(4, '69', '2024-11-28', 'FGGGG', 'ass', 'Silver', 56, '56969696969', 2.000, 45.00, 25.00, 55.00, 66.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL),
(5, '69', '2024-11-28', 'FGGGG', 'ass', 'Silver', 5, '56969696969', 2.000, 25.00, 22.00, 33.00, 23.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL),
(6, '69', '2024-11-28', 'FGGGG', 'ass', 'Silver', 56, '56969696969', 2.000, 55.00, 652.00, 232.00, 32.00, 32.00, 32.00, 32.00, 32.00, 3.00, 323.00, 0.00, NULL),
(7, '69', '2024-11-21', 'FGGGG', 'ass', 'Silver', 69, '56969696969', 2.000, 999.99, 69.00, 69.00, 6.00, 96.00, 9.00, 6.00, 9.00, 6.00, 969.00, 69.00, NULL),
(8, '70', '2024-12-03', 'GGbro', 'K', 'Gold', 0, '6956589856', 0.580, 91.75, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.22),
(9, '70', '2024-12-03', 'GGbro', 'K', 'Gold', 0, '6956589856', 5.000, 91.75, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00, 19.11),
(10, '100', '2024-12-03', 'GG Platinum', 'K', 'Platinum', 2, '101010101010', 7.000, 0.00, 95.30, 0.00, 0.00, 0.00, 0.00, 0.00, 2.00, 0.00, 0.00, 0.00, 0.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
