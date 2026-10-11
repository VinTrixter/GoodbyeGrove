-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2026 at 03:54 AM
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
-- Database: `funeral_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bookingID` int(11) NOT NULL,
  `clientID` int(11) NOT NULL,
  `venueID` int(11) NOT NULL,
  `empID` int(11) DEFAULT NULL,
  `deceasedName` varchar(50) DEFAULT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `daysUsed` int(11) NOT NULL,
  `totalPrice` decimal(10,2) NOT NULL,
  `bookingStatus` enum('Confirmed','Cancelled','Pending') NOT NULL DEFAULT 'Confirmed',
  `paymentStatus` enum('Paid','Partial','Pending') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bookingID`, `clientID`, `venueID`, `empID`, `deceasedName`, `startDate`, `endDate`, `daysUsed`, `totalPrice`, `bookingStatus`, `paymentStatus`) VALUES
(8, 107, 7, 6, 'George Cooper', '2026-09-30', '2026-10-10', 10, 45000.00, 'Confirmed', 'Paid'),
(9, 108, 8, 8, 'Harry Potter', '2026-10-01', '2026-10-03', 2, 20000.00, 'Cancelled', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `clientID` int(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `contactNum` varchar(20) NOT NULL,
  `emailAddr` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`clientID`, `firstName`, `lastName`, `contactNum`, `emailAddr`) VALUES
(107, 'John', 'Doe', '09296732123', 'johndoe@gmail.com'),
(108, 'Maria', 'Maquiling', '09873425623', 'maria@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `empID` int(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `role` enum('Staff','Administrator') NOT NULL DEFAULT 'Staff',
  `emailAddr` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`empID`, `firstName`, `lastName`, `role`, `emailAddr`, `password`) VALUES
(6, 'Vin Trixter', 'Gunot', 'Administrator', 'vin@goodbyegroove.com', 'testing123'),
(7, 'Jaelica', 'Fabian', 'Administrator', 'jaelicafabian@goodbyegroove.com', 'testing123'),
(8, 'Willie', 'Pajunar', 'Staff', 'willie@goodbyegroove.com', 'testing123');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `venueID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Chapel','Viewing room') NOT NULL,
  `capacity` int(11) NOT NULL,
  `availability` enum('Available','Booked','Maintenance') NOT NULL DEFAULT 'Available',
  `baseprice` decimal(10,2) NOT NULL,
  `imagePath` varchar(255) DEFAULT 'default_venue.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`venueID`, `name`, `type`, `capacity`, `availability`, `baseprice`, `imagePath`) VALUES
(7, 'Serenity Chapel Suite', 'Chapel', 60, 'Booked', 4500.00, 'uploads/venues/venue_6abd2200d127f.png'),
(8, 'Evergreen Grand Hall', 'Viewing room', 150, 'Available', 10000.00, 'uploads/venues/venue_6abd2504d90ca.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bookingID`),
  ADD KEY `fk_booking_client` (`clientID`),
  ADD KEY `fk_booking_venue` (`venueID`),
  ADD KEY `fk_booking_employee` (`empID`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`clientID`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`empID`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`venueID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `clientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `empID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `venueID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_client` FOREIGN KEY (`clientID`) REFERENCES `client` (`clientID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_employee` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_venue` FOREIGN KEY (`venueID`) REFERENCES `venue` (`venueID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
