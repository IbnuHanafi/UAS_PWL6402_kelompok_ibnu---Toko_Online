-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 03:27 PM
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
-- Database: `toko_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Elektronik', 'Produk elektronik dan gadget', '2025-06-24 09:28:49'),
(2, 'Fashion', 'Pakaian dan aksesoris', '2025-06-24 09:28:49'),
(3, 'Buku', 'Buku dan media pembelajaran', '2025-06-24 09:28:49'),
(4, 'Olahraga', 'Peralatan dan perlengkapan olahraga', '2025-06-24 09:28:49'),
(7, 'Aplikasi Premium', 'Nikmati berbagai fitur premium untuk aplikasi kesayangan anda', '2025-06-24 14:51:01'),
(8, 'Kuliner Instan', 'Nikmati Kuliner Instan yang mudah di buat dimanapun serta siap di santap', '2025-06-24 14:57:41');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `status`, `shipping_address`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 'ORD-20250624-7334', 25550000.00, 'cancelled', 'Jl. Customer No. 2', '', '2025-06-24 11:24:12', '2025-06-24 12:00:51'),
(2, 2, 'ORD-20250624-6137', 125000.00, 'processing', 'Jl. Customer No. 2', '', '2025-06-24 12:11:16', '2025-06-24 12:14:20'),
(4, 2, 'ORD-20250624-5283', 500000.00, 'completed', 'Jl. Customer No. 2', 'Beli bang', '2025-06-24 15:51:59', '2025-06-24 15:53:34');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 5, 1, 550000.00, 550000.00),
(2, 1, 1, 1, 25000000.00, 25000000.00),
(3, 2, 7, 1, 125000.00, 125000.00),
(5, 4, 12, 1, 500000.00, 500000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category_id`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Laptop Gaming ROG', 'Laptop gaming ASUS ROG dengan spesifikasi tinggi, RAM 16GB, SSD 1TB', 25000000.00, 5, 1, '685a89c0ca95a_1750763968.png', 'active', '2025-06-24 09:28:49', '2025-06-24 15:54:38'),
(2, 'Smartphone Samsung S23', 'Smartphone flagship Samsung Galaxy S23 dengan kamera 108MP', 15000000.00, 10, 1, '685a89b2d4668_1750763954.jpg', 'active', '2025-06-24 09:28:49', '2025-06-24 11:19:14'),
(3, 'Mouse Gaming RGB', 'Mouse gaming dengan lampu RGB dan DPI tinggi', 850000.00, 20, 1, '685a899fbd9da_1750763935.jpg', 'active', '2025-06-24 09:28:49', '2025-06-24 11:18:55'),
(4, 'Keyboard Mechanical', 'Keyboard mechanical dengan RGB light', 2100000.00, 15, 1, '685a8982b004a_1750763906.jpg', 'active', '2025-06-24 09:28:49', '2025-06-24 11:18:26'),
(5, 'Jaket Hoodie', 'Jaket hoodie premium dengan bahan berkualitas permium', 550000.00, 29, 2, '685a894ba3a3f_1750763851.jpg', 'active', '2025-06-24 09:28:49', '2025-06-24 11:24:12'),
(6, 'Sepatu Sneakers', 'Sepatu sneakers casual kekinian untuk sehari-hari', 1750000.00, 25, 2, '685a892e641ba_1750763822.jpeg', 'active', '2025-06-24 09:28:49', '2025-06-24 11:17:02'),
(7, 'Buku Programming PHP', 'Buku panduan lengkap programming PHP untuk pemula', 125000.00, 49, 3, '685a88fd56ef4_1750763773.jpeg', 'active', '2025-06-24 09:28:49', '2025-06-24 12:11:16'),
(8, 'Bola Sepak', 'Bola sepak resmi untuk pertandingan', 350000.00, 40, 4, '685a88e9dbff8_1750763753.jpg', 'active', '2025-06-24 09:28:49', '2025-06-24 11:15:53'),
(12, 'Youtube Premium  1 Tahun', 'Bebas iklan, dan berbagai fitur premium lainnya', 500000.00, 12, 7, '685abca3d3f3c_1750776995.jpg', 'active', '2025-06-24 14:56:35', '2025-06-24 15:51:59'),
(13, '1 box Indomie goreng bumbu ayam bawang goreng', 'Indomie goreng yang mudah di sajikan, 1 box isi 100', 100000.00, 99, 8, '685ae72856513_1750787880.jpeg', 'active', '2025-06-24 14:58:55', '2025-06-24 18:01:33'),
(14, 'Sarden Kaleng', 'Ikan sarden kaleng dengan bumbu repah steril mudah disajikan dan disantap', 27500.00, 32, 8, '685abd7e7c7c5_1750777214.jpg', 'active', '2025-06-24 15:00:14', '2025-06-24 15:00:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `reset_token`, `reset_token_expires_at`, `reset_expires`, `role`, `full_name`, `phone`, `address`, `created_at`) VALUES
(1, 'admin', 'admin@tokooline.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 'admin', 'Administrator', '08123456789', 'Jl. Admin No. 1', '2025-06-24 09:28:49'),
(2, 'customer1', 'customer@tokooline.com', '$2y$10$47MpXavsZozjWW0bN/b5oeG128ADTkt3WegWUorG/EGoKJRyDMCDW', NULL, NULL, NULL, 'customer', 'Mr. Customer', '08987654321', 'Jl. Customer No. 2', '2025-06-24 09:28:49'),
(3, 'IbnuHanafi1', 'ibnuhanafi56289@gmail.com', '$2y$10$Fve8r1qA2jx2VOWiB7Cuye6npjtogp2Wpfjt3Et2i40RutdvrYDo2', '50cf1c5ba1c23343f4c051bb060e1c92129f433e40f09bef7b32c6d2db4039e4', '2025-06-25 08:16:58', '2025-06-24 21:53:34', 'customer', 'Ibnu Hanafi Assalam', '088802972620', 'Semarang city, Central Java', '2025-06-24 12:58:45'),
(4, 'ibnuke2', 'ibnuhanafi3643@gmail.com', '$2y$10$4M6Te6UfNBWl/OndvsIum.IpAu4W3L/oCCCRuWyountrYFS66csK.', NULL, NULL, NULL, 'customer', 'ibnu2', '0882003427668', 'Semarang city, Central Java', '2025-06-25 07:26:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
