-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 29, 2025 at 09:54 AM
-- Server version: 8.4.0
-- PHP Version: 8.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bus_ticket`
--

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `bus_id` int NOT NULL,
  `bus` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `number_plate` text NOT NULL,
  `seats` int NOT NULL,
  `home` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`bus_id`, `bus`, `number_plate`, `seats`, `home`) VALUES
(1, 'Marco Polo', 'ADE 3442', 10, 'Kitwe'),
(2, 'Scarnia', 'AOB 4470', 10, 'Ndola'),
(3, 'Rosa', 'ACK 9989', 10, 'Chingola'),
(4, 'Scarnia', 'ADE 2424', 10, 'Kitwe'),
(5, 'Mercedes Bus', 'ACK 2945', 7, 'Ndola'),
(6, 'Tata Bus', 'BCE 1987', 12, 'Lusaka');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `location` text NOT NULL,
  `position` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `email`, `username`, `password`, `location`, `position`) VALUES
(1, 'admin@admin', 'admin', '$2y$10$i3Z/x4EHnqk2GA0DqB6n0O15u2yDAUCpEIClO6iniVV5nXs501akm', 'Kitwe', 'admin'),
(2, 'peter@gmail.com', 'peter', '$2y$10$P9SQJSg5sry5MAO3s0xWN.ZNAZDNEmUzA7bX2LT1FGvYdqTTKkJvq', 'Chingola', NULL),
(3, 'jacob@gmail.com', 'jacob', '$2y$10$UCyqUhWkIQwbRyb6x7XSZ.CC4kLuQUPVk0aP0mL1gkAUW7t4mv9.S', 'Kitwe', NULL),
(5, 'john@gmail.com', 'john', '$2y$10$d30dNxw2m3Y25xwAYsJicev6NRV5jqRIPIkyLX7RlPMgm7UjcgxRa', 'Lusaka', NULL),
(7, 'paul@gmail.com', 'paul', '$2y$10$oTa1upWTffwWNq0YmH13Mu0aqllQhykx5QLWrySOFfvT80smLCx4C', 'Kitwe', NULL),
(8, 'jane@gmail.com', 'jane', '$2y$10$zPxqD.45KRAqUmL6IRP5DeECwIQtT/boGCUT5HfndAS5azp6O9k3y', 'Chingola', NULL),
(9, 'nancy@gmail.com', 'nancy', '$2y$10$tfyoOjmz/N.sWzgfmBH9FuNRM5FLAd3wxIUg0N4AVtiUs4Erzc2ou', 'Lusaka', NULL),
(10, 'mary@gmail.com', 'Mary', '$2y$10$tXzY5xrVUkWMh7g1mW7gQen0w3OpLgNpBeK4fqcSBgUa2ITzHiyTS', 'Chingola', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `trip_id` int NOT NULL,
  `seat` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `trip_id`, `seat`) VALUES
(7, 3, 1, 2),
(8, 3, 11, 10),
(9, 2, 4, 7),
(10, 2, 5, 8),
(11, 5, 12, 1),
(12, 5, 15, 4),
(13, 5, 15, 3),
(14, 7, 15, 5),
(15, 7, 4, 10),
(16, 8, 12, 7),
(17, 8, 4, 1),
(18, 9, 14, 2),
(19, 9, 1, 1),
(20, 10, 10, 3),
(21, 10, 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `trip_id` int NOT NULL,
  `bus_id` int NOT NULL,
  `home` text NOT NULL,
  `destination` text NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time NOT NULL,
  `date` date NOT NULL,
  `seats` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`trip_id`, `bus_id`, `home`, `destination`, `departure_time`, `arrival_time`, `date`, `seats`) VALUES
(1, 1, 'Kitwe', 'Lusaka', '05:30:00', '15:00:00', '2025-05-01', '[3, 4, 5, 6, 7, 8, 9, 10]'),
(2, 2, 'Ndola', 'Lusaka', '12:12:00', '12:12:00', '2025-05-01', '[2, 3, 4, 5, 6, 7, 8, 9, 10]'),
(3, 6, 'Ndola', 'Kitwe', '07:00:00', '08:30:00', '2025-05-01', '[1, 2, 3, 4, 5, 6, 7]'),
(4, 6, 'Lusaka', 'Chingola', '05:00:00', '14:00:00', '2025-05-01', '[2, 3, 4, 5, 6, 8, 9, 11, 12]'),
(5, 3, 'Chingola', 'Ndola', '09:00:00', '13:00:00', '2025-05-01', '[1, 2, 3, 4, 5, 6, 7, 9, 10]'),
(10, 3, 'Lusaka', 'Kitwe', '12:30:00', '22:00:00', '2025-05-02', '[2, 4, 5, 6, 7, 8, 9, 10]'),
(11, 1, 'Chingola', 'Lusaka', '05:30:00', '14:00:00', '2025-05-02', '[1, 2, 3, 4, 5, 6, 7, 8, 9]'),
(12, 2, 'Kitwe', 'Ndola', '09:00:00', '11:00:00', '2025-05-02', '[2, 3, 4, 5, 6, 8, 9, 10]'),
(13, 5, 'Kitwe', 'Chingola', '12:00:00', '14:00:00', '2025-05-02', '[1, 2, 3, 4, 5, 6, 7]'),
(14, 3, 'Lusaka', 'Chingola', '07:00:00', '22:00:00', '2025-05-02', '[1, 3, 4, 5, 6, 7, 8, 9, 10]'),
(15, 6, 'Ndola', 'Lusaka', '05:00:00', '16:00:00', '2025-05-02', '[1, 2, 6, 7, 8, 9, 10, 11, 12]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`bus_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`trip_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `bus_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `trip_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
