-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 18, 2025 at 05:25 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grocery_chain`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `address`, `phone`) VALUES
(1, 'شعبه مرکزی تهران', 'خیابان زعفرانیه پلاک 22', '02122901240'),
(2, 'شعبه شرق تهران', 'تهرانپارس، فلکه دوم', '02177888000'),
(3, 'شعبه غرب تهران', 'سعادت‌آباد، میدان کاج', '02144113340');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `mobile` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `mobile`, `full_name`, `created_at`) VALUES
(1, '09128889595', 'احسان', '2025-09-16 12:31:03'),
(3, '09121112345', 'حسینی', '2025-09-17 12:44:41'),
(4, '09121111111', 'مشتری عمومی', '2025-09-17 14:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `total_amount` int DEFAULT NULL,
  `payment_method` enum('نقدی','POS','کارت به کارت','ترکیبی') COLLATE utf8mb4_unicode_ci DEFAULT 'نقدی',
  `status` enum('موقت','نهایی','مرجوعی') COLLATE utf8mb4_unicode_ci DEFAULT 'موقت',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `customer_id`, `user_id`, `branch_id`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 1010000, 'نقدی', 'نهایی', '2025-09-16 12:31:03'),
(2, 1, 2, 1, 630000, 'ترکیبی', 'نهایی', '2025-09-17 12:40:48'),
(3, 1, 2, 1, 630000, 'ترکیبی', 'نهایی', '2025-09-17 12:42:10'),
(4, 1, 2, 1, 630000, 'ترکیبی', 'نهایی', '2025-09-17 12:43:46'),
(6, 3, 2, 1, 250000, 'ترکیبی', 'نهایی', '2025-09-17 12:53:56'),
(7, 1, 2, 2, 400000, 'ترکیبی', 'نهایی', '2025-09-17 13:27:21'),
(8, 4, 4, 3, 4000000, 'ترکیبی', 'نهایی', '2025-09-17 14:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `unit_price` int DEFAULT NULL,
  `total_price` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 2, 2, 380000, 760000),
(2, 1, 1, 1, 250000, 250000),
(3, 2, 1, 1, 250000, 250000),
(4, 2, 2, 1, 380000, 380000),
(5, 3, 1, 1, 250000, 250000),
(6, 3, 2, 1, 380000, 380000),
(7, 4, 1, 1, 250000, 250000),
(8, 4, 2, 1, 380000, 380000),
(9, 6, 1, 1, 250000, 250000),
(10, 7, 5, 1, 400000, 400000),
(11, 8, 4, 2, 2000000, 4000000);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `cash` int DEFAULT '0',
  `pos` int DEFAULT '0',
  `card2card` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `invoice_id`, `cash`, `pos`, `card2card`, `created_at`) VALUES
(1, 4, 400000, 130000, 100000, '2025-09-17 12:43:46'),
(3, 6, 50000, 200000, 0, '2025-09-17 12:53:56'),
(4, 7, 0, 400000, 0, '2025-09-17 13:27:21'),
(5, 8, 0, 4000000, 0, '2025-09-17 14:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sale_price` int NOT NULL,
  `buy_price` int NOT NULL,
  `final_price` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `stock` int DEFAULT '0',
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `color`, `sale_price`, `buy_price`, `final_price`, `image_path`, `branch_id`, `stock`, `barcode`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'P001', 'پیراهن مردانه', 'آبی', 250000, 180000, 200000, NULL, 1, 5, '1234567890123', '2025-09-16 11:51:23', '2025-09-17 12:53:56', 1, NULL),
(2, 'P002', 'شلوار جین', 'مشکی', 380000, 280000, 320000, 'uploads/68cac8c3ccac4.jpg', 1, 1, '1234567890124', '2025-09-16 11:51:23', '2025-09-17 18:12:11', 1, 1),
(3, 'P003', 'تیشرت نخی', 'سفید', 150000, 100000, 120000, NULL, 1, 15, '1234567890125', '2025-09-16 11:51:23', '2025-09-17 18:31:32', 1, 1),
(4, '8515', 'کیف سر دوشی', 'صورتی', 2000000, 1700000, 1950000, 'uploads/68c9284198a82.jpg', 3, 3, '', '2025-09-16 12:35:05', '2025-09-17 18:31:41', 1, 1),
(5, '8622', 'کیف بغلی', 'آبی', 400000, 300000, 3500000, 'uploads/68c928858a4a3.jpg', 2, 5, '', '2025-09-16 12:36:13', '2025-09-17 18:31:19', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `refund_amount` int DEFAULT NULL,
  `refund_method` enum('نقدی','کارت به کارت','شبا','ترکیبی') COLLATE utf8mb4_unicode_ci DEFAULT 'نقدی',
  `bank_info` text COLLATE utf8mb4_unicode_ci,
  `status` enum('در حال بررسی','واریز شده') COLLATE utf8mb4_unicode_ci DEFAULT 'در حال بررسی',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` int DEFAULT NULL,
  `transaction_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`id`, `invoice_id`, `product_id`, `quantity`, `reason`, `refund_amount`, `refund_method`, `bank_info`, `status`, `created_at`, `confirmed_at`, `confirmed_by`, `transaction_code`) VALUES
(1, 8, 4, 1, 'تست کاربر امتحانی4', 2000000, 'کارت به کارت', '62631418989809876', 'واریز شده', '2025-09-17 14:12:09', '2025-09-17 18:37:42', 1, '43425678');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('مدیر','حسابدار','فروشنده') COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `mobile`, `email`, `role`, `branch_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$KpOQCsypigBGVNJyBcvom.ut6O9TNq8eFx2BXs3OX0ooboabZ/YMu', 'مدیر سیستم', '09120000000', 'admin@shop.ir', 'مدیر', 1, '2025-09-16 11:51:23'),
(2, 'ehsan', '$2y$10$0jodp5aBFCAyaHRyl4rjY.nJnLunwIDNiie2wvrQkEyfSbJvR2PWG', 'احسان', '0912999909', 'ehsan@gmail.com', 'فروشنده', 2, '2025-09-16 12:12:05'),
(3, 'acc', '$2y$10$.xNN9SYH7QmH.Uigp45Lqu6vPt1BX222T8dHaHgNSBJXNUs91NGtq', 'hesabdar', '09128876543', 'acc@gmail.com', 'حسابدار', 1, '2025-09-16 12:12:52'),
(4, 'test', '$2y$10$f1dKTpJowsFmf6QabnhMD.ryQW4D/gYK0DBuxGNpAT3wLQWqEe0Nm', 'تست3', '09126665432', 'test@g.com', 'فروشنده', 3, '2025-09-16 12:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `wastes`
--

CREATE TABLE `wastes` (
  `id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `branch_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `confirmed_by` int DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wastes`
--

INSERT INTO `wastes` (`id`, `product_id`, `quantity`, `reason`, `branch_id`, `created_by`, `created_at`, `confirmed_by`, `confirmed_at`) VALUES
(5, 4, 1, 'کیف پاره بود', 3, 4, '2025-09-17 14:41:19', 1, '2025-09-17 15:23:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `confirmed_by` (`confirmed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `wastes`
--
ALTER TABLE `wastes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `confirmed_by` (`confirmed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wastes`
--
ALTER TABLE `wastes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `wastes`
--
ALTER TABLE `wastes`
  ADD CONSTRAINT `wastes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `wastes_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `wastes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wastes_ibfk_4` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
