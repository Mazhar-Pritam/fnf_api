-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2026 at 09:06 AM
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
-- Database: `fnf_api`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `created_at`, `updated_at`, `deleted_at`) VALUES
(27, 'Engine Parts', 'Pistons, spark plugs, engine valves, gaskets, timing belts', 'category/1173012241enginepart.jpeg', NULL, NULL, NULL),
(28, 'Brake System', 'Brake pads, brake discs, brake shoes, brake fluid', 'category/1922559637breakpads.jpeg', NULL, NULL, NULL),
(29, 'Filters', 'Air filters, oil filters, fuel filters, cabin filters', 'category/1714200361filters.jpeg', NULL, NULL, NULL),
(30, 'Electrical Parts', 'Batteries, alternators, starter motors, fuses, relays', 'category/1867673713motor.jpeg', NULL, NULL, NULL),
(31, 'Suspension & Steering', 'Shock absorbers, ball joints, tie rods, wheel bearings', 'category/991446870suspension.jpeg', NULL, NULL, NULL),
(32, 'Suspension & Steering', 'Heavy Bikes', 'category/1380115931suspension.jpeg', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'MaZhar Pritom', '21542244', 'mazhar.pritom@gmail.com', 'Chandgaon', '2026-10-17 03:54:58', NULL, NULL),
(4, 'MaZhar Pritom', '', 'mazhar.pritom@gmail.com', 'Chandgaon', '2026-10-17 04:13:17', NULL, NULL),
(5, 'Ma', '', 'mazhar.pritom@gmail.com', 'Chandgaon', '2026-10-17 04:13:21', NULL, NULL),
(6, 'MaZhar Pritom', '29841', 'mazhar.pritom@gmail.com', 'Chandgaon', '2026-10-17 04:13:25', NULL, NULL),
(11, 'M', '21542244', 'mazhar.pritom@gmail.com', 'Chandgaon', '2026-10-17 05:05:08', NULL, NULL),
(12, 'MaZh', '21542244', 'mazhar.pritom@gmail.com', 'Chandgaon', '2026-10-17 05:57:37', NULL, NULL),
(13, 'azad', '02254444', 'akib@idb.com', 'Chandgaon', '2026-10-18 04:10:24', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parts`
--

CREATE TABLE `parts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parts`
--

INSERT INTO `parts` (`id`, `name`, `part_number`, `category_id`, `purchase_price`, `sale_price`, `stock_quantity`, `description`, `image`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'Break Pads', '52444', 28, 455.00, 0.00, 100, 'f', 'part/241601084breakpads.jpeg', 1, NULL, NULL, NULL),
(5, 'Suspension & Steering', '52444', 31, 0.00, 0.00, 130, 'f', 'part/885300817suspension.jpeg', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `invoice_no`, `supplier_id`, `purchase_date`, `total_amount`, `note`, `created_at`, `status`, `updated_at`, `deleted_at`) VALUES
(8, '654522', 14, '2026-10-20', 45500.00, 'Due', '2026-10-18 06:59:16', 1, NULL, NULL),
(9, '654522', 15, '2026-10-22', 45500.00, 'Due', '2026-10-18 07:02:17', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `part_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES
(1, 6, 3, 100, 380.00, 38000.00, '2026-10-18 06:36:37'),
(2, 7, 3, 1000, 380.00, 380000.00, '2026-10-18 06:38:43'),
(3, 8, 4, 100, 455.00, 45500.00, '2026-10-18 06:59:16'),
(4, 9, 5, 130, 350.00, 45500.00, '2026-10-18 07:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(14, 'Kalu and co', 'MaZhar Pritom', '02222', 'mazhar.pritom@gmail.com', 'Chandgaon', 1, NULL, NULL, NULL),
(15, 'nalu and co', 'nalu', '0256565', 'dddd@gg', 'Chandgaon', 1, NULL, NULL, NULL),
(16, 'Ma', 'MaZhar Pritom', '420', 'mazhar.pritom@gmail.com', 'Chandgaon', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact_no` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `remember_token` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `contact_no`, `email`, `password`, `status`, `remember_token`, `image`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Kamal Uddin', NULL, 'kamal@yahoo.com', '738f69d66dd207d6184e396e0c7e6bd582165627', 1, '14201791604761119689', NULL, NULL, NULL, NULL),
(3, 'MaZhar Pritom', NULL, 'tom@gmail.com', 'b986415c93241513d33d01fcf532a6c47ac4f3ee', 1, '6398afc25a7792b0bade753ad2f3550606d3b07c53eec07110b96786e9a83ae8', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
