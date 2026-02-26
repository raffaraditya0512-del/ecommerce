-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 06:51 AM
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
-- Database: `ecommerce_radit`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` int(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default-product.jpg',
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `stock`, `created_at`, `updated_at`) VALUES
(5, 'jaket kulit', 100000.00, 0, '', 'default-product.jpg', 5, '2026-02-23 04:42:23', '2026-02-26 04:46:03'),
(6, 'work jacket', 100000.00, 0, '', 'default-product.jpg', 3, '2026-02-23 04:48:56', '2026-02-25 13:40:46'),
(7, 'kaos kaki', 100000.00, 0, '', 'default-product.jpg', 5, '2026-02-24 08:50:41', '2026-02-26 04:50:26');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `product_id`, `quantity`, `total_price`, `status`, `payment_method`, `created_at`, `updated_at`) VALUES
(17, 18, 5, 1, 100000.00, 'pending', 'cod', '2026-02-26 04:38:34', '2026-02-26 04:38:34'),
(20, 12, 5, 5, 500000.00, 'processing', 'cash', '2026-02-26 05:23:10', '2026-02-26 05:23:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(3, 'admin', 'admin@ecommerce.com', '$2y$10$kCrOqk7qlCv5BQyuGtg2h.oCyNyOqSmPvp79DtRTb2mjwZ.YxaIXK', 'admin', '2026-02-10 09:25:28', '2026-02-10 09:25:28'),
(7, 'admin123', 'masudjr@example.com', '$2y$10$3I1dwvtaqALAsbOpIf0t9evqdwjOMIGTarxvUEkNML1zLzfzIBGxS', 'user', '2026-02-11 03:44:48', '2026-02-11 03:44:48'),
(11, 'raffa', 'alifadiyaksa1@gmail.com', '$2y$10$OaQwIl8BgrIO8RiucjGgfOJYrGyNhFa38u9cLDOmbpCgd6ezdgbMW', 'user', '2026-02-20 07:03:15', '2026-02-20 07:03:15'),
(12, 'frimpong', 'jeremiefrimpong@gmail.com', '$2y$10$.JEqyYkqbcbElPWKaBxAquXrQNqRmD181ORu8wylyEFmK./QmGnv2', 'user', '2026-02-20 08:11:20', '2026-02-26 01:43:25'),
(13, 'budi_santoso', 'budisantoso@gmail.com', '$2y$10$US9DipR.oXS0hkf8FtslUedU5wFNXkTL9EkPopIB8TcGI/4udW0He', 'petugas', '2026-02-20 08:43:19', '2026-02-26 01:39:40'),
(16, 'alif_adiyaksa', 'siealipp@gmail.com', '$2y$10$JkPoAHlLgef6LVvknh9dw.bhcE1NfwfcazWZ2xCy61kGBt8JhNQF6', 'petugas', '2026-02-20 08:43:20', '2026-02-20 15:07:53'),
(18, 'raffar', 'raffaraditya0512@gmail.com', '$2y$10$mZyrREAlUzvdzQZiPVUfjuHoR5YZDHtn/AUuffuUjzarlBTdTNOZe', 'user', '2026-02-20 14:02:32', '2026-02-20 14:02:32'),
(23, 'masud', 'muasudd@gmail.com', '$2y$10$bT5Rmlhei8ioI3a6zFa4MOZfv3wrDFPLHWYlPdZ/VqZk/9jltUeo.', 'user', '2026-02-26 03:17:46', '2026-02-26 03:17:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
