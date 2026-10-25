-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2026 at 08:04 AM
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
(33, 'Cooling System', 'Radiator, water pump, thermostat, cooling fan, radiator hose, coolant.', 'category/511128156radiator.jpeg', NULL, NULL, NULL),
(34, 'Wheels & Tires', 'Tires, alloy wheels, wheel bearings, wheel nuts, tire valves.', 'category/2094368201tyres.jpeg', NULL, NULL, NULL),
(35, 'Oils & Fluids', 'Engine oil, gear oil, brake fluid, coolant, power steering fluid, transmission fluid.', 'category/1289535081enginepart.jpeg', NULL, NULL, NULL),
(36, 'Lights & Accessories', '  Headlights, tail lights, indicator lamps, bulbs, horns, wipers, mirrors', 'category/449938087lights.jpeg', NULL, NULL, NULL);

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
(14, 'Ibrahim Ahmed', '01712345678	', 'ibra@example.com', 'Agrabad, Chattogram', '2026-10-25 03:46:51', NULL, NULL),
(15, 'Karim Hasan	', '01812345678	', 'karim@example.com', 'Mirpur, Dhaka', '2026-10-25 03:48:02', NULL, NULL),
(16, 'Nusrat Jahan	', '01912345678	', 'nusrat@example.com', 'GEC Circle, Chattogram', '2026-10-25 03:48:34', NULL, NULL),
(17, 'Tanvir Hossain	', '01612345678	', 'tanvir@example.com', 'Uttara, Dhaka', '2026-10-25 03:49:17', NULL, NULL),
(18, 'Sadia Akter	', '01512345678	', 'sadia@example.com', 'Panchlaish, Chattogram', '2026-10-25 03:50:01', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` varchar(20) NOT NULL DEFAULT 'Cash',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_date`, `category`, `amount`, `method`, `note`, `created_at`, `deleted_at`) VALUES
(6, '2026-10-20', 'Salary', 15000.00, 'Cash', 'Cash', '2026-10-25 06:33:20', NULL),
(7, '2026-10-12', 'Electricity', 5000.00, 'Cash', '', '2026-10-25 06:33:50', NULL);

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
  `deleted_at` datetime DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `minimum_stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parts`
--

INSERT INTO `parts` (`id`, `name`, `part_number`, `category_id`, `purchase_price`, `sale_price`, `stock_quantity`, `description`, `image`, `status`, `created_at`, `updated_at`, `deleted_at`, `brand`, `vehicle_model`, `minimum_stock`) VALUES
(8, 'Spark Plug	', 'ENG-NGK-001	', 27, 350.00, 550.00, 8, 'Spark plug for compatible petrol engines.', 'part/942062176plugs.jpeg', 1, NULL, NULL, NULL, 'NGK	', 'Toyota Corolla	', 10),
(9, 'Front Brake Pad Set	', 'BRK-BPD-001	', 28, 1200.00, 1800.00, 85, 'Front brake pad set for compatible Toyota Axio models.', 'part/1263617754break.jpeg', 1, NULL, NULL, NULL, 'Advics	', 'Toyota Axio	', 5),
(10, 'Engine Oil Filter', 'FIL-OIL-001', 29, 350.00, 530.00, 386, 'Replacement engine oil filter for compatible Toyota engines.', 'part/1727399641oil.jpeg', 1, NULL, NULL, NULL, 'Denso', 'Toyota Corolla', 10),
(11, 'Car Battery 12V', 'ELE-BAT-001	', 30, 8000.00, 10000.00, 70, '12V automotive battery; confirm capacity and dimensions before installation.', 'part/603069413gs.jpeg', 1, NULL, NULL, NULL, 'GS	', 'Toyota Allion', 10),
(12, 'Shock Absorber	', 'SUS-SHA-001	', 31, 3000.00, 4800.00, 100, 'Suspension shock absorber for compatible Toyota Premio models.', 'part/821749293suspension.jpeg', 1, NULL, NULL, NULL, 'KYB', 'Toyota Premio', 10),
(13, 'Car Tire 14-inch	', 'WHL-TIR-001	', 34, 4500.00, 5800.00, 55, '14-inch tire; verify tire width, aspect ratio, and load rating.', 'part/1071594334tyressss.jpeg', 1, NULL, NULL, NULL, 'Bridgestone', 'Toyota Vitz	', 5),
(14, 'Engine Oil Shell Helix  HX7 10W-40 4L	', 'OIL-SHL-001	', 35, 2800.00, 3500.00, 140, 'Semi-synthetic SAE 10W-40 engine oil in a 4-litre pack.', 'part/921346138oil.jpeg', 1, NULL, NULL, NULL, 'Shell	', 'Toyota Corolla	', 20),
(15, 'Headlight Assembly', 'LGT-HDL-001', 36, 4000.00, 5500.00, 87, 'Replacement headlight assembly for compatible Toyota Premio models.', 'part/1513423744head.jpeg', 1, NULL, NULL, NULL, 'Koito	', 'Toyota Premio', 10);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_no` varchar(50) NOT NULL,
  `type` varchar(10) NOT NULL,
  `party_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` varchar(20) NOT NULL DEFAULT 'Cash',
  `payment_date` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_no`, `type`, `party_id`, `amount`, `method`, `payment_date`, `note`, `created_at`, `deleted_at`) VALUES
(1, 'pay', 'receipt', 15, 212000.00, 'Bank', '2026-10-25', 'paid', '2026-10-25 05:20:26', NULL),
(2, 'pay02', 'receipt', 16, 365950.00, 'Cash', '2026-10-26', 'paid', '2026-10-25 05:21:12', NULL),
(3, 'pay02', 'payment', 19, 575000.00, 'Cash', '2026-10-26', 'Bank', '2026-10-25 05:22:45', NULL),
(4, 'pay05', 'payment', 21, 1600000.00, 'Cash', '2026-10-20', 'Bank', '2026-10-25 05:24:23', NULL);

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
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `invoice_no`, `supplier_id`, `purchase_date`, `total_amount`, `paid_amount`, `note`, `created_at`, `status`, `updated_at`, `deleted_at`) VALUES
(1, 'PUR-2026-001', 18, '2026-10-14', 260000.00, 260000.00, 'Monthly engine parts stock replenishment.', '2026-10-25 04:58:39', 1, NULL, NULL),
(2, 'PUR-2026-002', 19, '2026-10-13', 975000.00, 400000.00, 'Monthly parts stock replenishment.', '2026-10-25 05:00:11', 1, NULL, NULL),
(3, 'PUR-2026-003', 21, '2026-10-26', 1600000.00, 0.00, 'Monthly parts stock replenishment.', '2026-10-25 05:05:38', 1, NULL, NULL);

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
  `sale_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `part_id`, `quantity`, `unit_price`, `sale_price`, `line_total`, `created_at`) VALUES
(1, 1, 8, 100, 350.00, 550.00, 35000.00, '2026-10-25 04:58:39'),
(2, 1, 9, 100, 1200.00, 1800.00, 120000.00, '2026-10-25 04:58:39'),
(3, 1, 10, 300, 350.00, 530.00, 105000.00, '2026-10-25 04:58:39'),
(4, 2, 10, 100, 350.00, 530.00, 35000.00, '2026-10-25 05:00:11'),
(5, 2, 11, 80, 8000.00, 10000.00, 640000.00, '2026-10-25 05:00:11'),
(6, 2, 12, 100, 3000.00, 4800.00, 300000.00, '2026-10-25 05:00:11'),
(7, 3, 13, 80, 4500.00, 5800.00, 360000.00, '2026-10-25 05:05:38'),
(8, 3, 14, 300, 2800.00, 3500.00, 840000.00, '2026-10-25 05:05:38'),
(9, 3, 15, 100, 4000.00, 5500.00, 400000.00, '2026-10-25 05:05:38');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` int(11) NOT NULL,
  `return_no` varchar(50) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `return_no`, `purchase_id`, `supplier_id`, `return_date`, `total_amount`, `note`, `created_at`, `deleted_at`) VALUES
(1, 'ret-01', 3, 21, '2026-10-22', 181000.00, '', '2026-10-25 06:41:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` int(11) NOT NULL,
  `purchase_return_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_return_items`
--

INSERT INTO `purchase_return_items` (`id`, `purchase_return_id`, `part_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES
(1, 1, 13, 10, 4500.00, 45000.00, '2026-10-25 06:41:15'),
(2, 1, 14, 30, 2800.00, 84000.00, '2026-10-25 06:41:15'),
(3, 1, 15, 13, 4000.00, 52000.00, '2026-10-25 06:41:15');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_no`, `customer_id`, `sale_date`, `total_amount`, `paid_amount`, `note`, `created_at`, `deleted_at`) VALUES
(1, 'Sale-2026-01', 14, '2026-10-19', 45420.00, 45420.00, 'Sale to ibra', '2026-10-25 05:11:18', NULL),
(2, 'Sale-2026-02', 15, '2026-10-27', 362000.00, 150000.00, 'Due n cash', '2026-10-25 05:13:56', NULL),
(3, 'Sale-2026-03', 16, '2026-10-19', 365950.00, 0.00, 'Due', '2026-10-25 05:15:48', NULL),
(4, 'Sale-2026-05', 18, '2026-10-19', 24750.00, 24750.00, 'Cash', '2026-10-25 05:16:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `part_id`, `quantity`, `unit_price`, `cost_price`, `line_total`, `created_at`) VALUES
(1, 1, 8, 20, 550.00, 350.00, 11000.00, '2026-10-25 05:11:19'),
(2, 1, 9, 15, 1800.00, 1200.00, 27000.00, '2026-10-25 05:11:19'),
(3, 1, 10, 14, 530.00, 350.00, 7420.00, '2026-10-25 05:11:19'),
(4, 2, 11, 10, 10000.00, 8000.00, 100000.00, '2026-10-25 05:13:56'),
(5, 2, 13, 15, 5800.00, 4500.00, 87000.00, '2026-10-25 05:13:56'),
(6, 2, 14, 50, 3500.00, 2800.00, 175000.00, '2026-10-25 05:13:56'),
(7, 3, 14, 100, 3500.00, 2800.00, 350000.00, '2026-10-25 05:15:48'),
(8, 3, 8, 29, 550.00, 350.00, 15950.00, '2026-10-25 05:15:48'),
(9, 4, 8, 45, 550.00, 350.00, 24750.00, '2026-10-25 05:16:32');

-- --------------------------------------------------------

--
-- Table structure for table `sale_returns`
--

CREATE TABLE `sale_returns` (
  `id` int(11) NOT NULL,
  `return_no` varchar(50) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_returns`
--

INSERT INTO `sale_returns` (`id`, `return_no`, `sale_id`, `customer_id`, `return_date`, `total_amount`, `note`, `created_at`, `deleted_at`) VALUES
(1, 'retsales01', 3, 16, '2026-10-25', 71100.00, '', '2026-10-25 06:42:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sale_return_items`
--

CREATE TABLE `sale_return_items` (
  `id` int(11) NOT NULL,
  `sale_return_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_return_items`
--

INSERT INTO `sale_return_items` (`id`, `sale_return_id`, `part_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES
(1, 1, 14, 20, 3500.00, 70000.00, '2026-10-25 06:42:09'),
(2, 1, 8, 2, 550.00, 1100.00, '2026-10-25 06:42:09');

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
(18, 'Auto Parts BD', 'Akibul Islam', '01712345678', 'info@autopartsbd.example', 'Agrabad, Chattogram, Bangladesh', 1, NULL, NULL, NULL),
(19, 'Dhaka Motor Supplies', 'Karim Hasan', '01812345678', 'info@dhakamotors.example', 'Mirpur, Dhaka', 1, NULL, NULL, NULL),
(20, 'Bangladesh Auto Traders	', 'Sohel Rana	', '01912345678	', 'sales@autotraders.example', 'Tejgaon, Dhaka', 1, NULL, NULL, NULL),
(21, 'Japan Car Parts House', 'Tanvir Hossain	', '01612345678	', 'info@japancarparts.example', 'Agrabad, Chattogram', 1, NULL, NULL, NULL);

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
(3, 'MaZhar Pritom', NULL, 'tom@gmail.com', 'b986415c93241513d33d01fcf532a6c47ac4f3ee', 1, '9078dfc7a9f455df5610f399d48eeb5f5a439199c096bfa18b101257c4f99f48', NULL, NULL, NULL, NULL);

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
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`,`party_id`);

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
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_return_id` (`purchase_return_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_return_id` (`sale_return_id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sale_returns`
--
ALTER TABLE `sale_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
