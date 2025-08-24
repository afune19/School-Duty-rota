-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2025 at 05:04 PM
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
-- Database: `duty_rota`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3');

-- --------------------------------------------------------

--
-- Table structure for table `rota`
--

CREATE TABLE `rota` (
  `id` int(11) NOT NULL,
  `week` varchar(50) NOT NULL,
  `teacher` text NOT NULL,
  `duty_type` enum('WEEKLY','LUNCH') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rota`
--

INSERT INTO `rota` (`id`, `week`, `teacher`, `duty_type`) VALUES
(1, 'Week 1 (Opening)', 'Charles Odeny, Alice Sarange', 'WEEKLY'),
(2, 'Week 2', 'Henry Malex, Reginah Musengya', 'WEEKLY'),
(3, 'Week 3', 'Vincent Cheneri, Jackline Nduva', 'WEEKLY'),
(4, 'Week 4 (Midterm)', 'Ian Sagala, Rahab Meeme', 'WEEKLY'),
(5, 'Week 5', 'Ismael Yamboko, Stellah Simiyu', 'WEEKLY'),
(6, 'Week 6', 'Mwetu Onesmus, Nancy Kamau', 'WEEKLY'),
(7, 'Week 7', 'Benson Melchzedek, Manea Nakhanu', 'WEEKLY'),
(8, 'Week 8', 'Mark Odhiambo, Faith Mutie', 'WEEKLY'),
(9, 'Week 9', 'Timothy Muhoho, Gertrude Ndunge', 'WEEKLY'),
(10, 'Week 10', 'Brian Chesoni Wanyonyi, Rhodah Migosi', 'WEEKLY'),
(11, 'Week 11', 'Evans Mutua, Sarah Achieng', 'WEEKLY'),
(12, 'Week 12', 'Ismael Yamboko, Nancy Kamau', 'WEEKLY'),
(13, 'Week 13', 'Vincent Cheneri, Jackline Nduva', 'WEEKLY'),
(14, 'Week 14 (Closing)', 'Henry Malex, Stellah Simiyu', 'WEEKLY'),
(15, 'Monday', 'Evans Mutua, Brian Chesoni Wanyonyi, Jackline Nduva, Reginah Musengya', 'LUNCH'),
(16, 'Tuesday', 'Vincent Cheneri, Mwetu Onesmus, Alice Sarange, Nancy Kamau', 'LUNCH'),
(17, 'Wednesday', 'Ismael Yamboko, Ian Sagala, Stellah Simiyu, Sarah Achieng', 'LUNCH'),
(18, 'Thursday', 'Mark Odhiambo, Henry Malex, Rahab Meeme, Rhodah Migosi', 'LUNCH'),
(19, 'Friday', 'Charles Odeny, Timothy Muhoho, Manea Nakhanu, Faith Mutie', 'LUNCH');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `rota`
--
ALTER TABLE `rota`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rota`
--
ALTER TABLE `rota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
