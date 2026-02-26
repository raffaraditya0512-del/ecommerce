-- Backup Database E-Commerce Radit
-- Generated: 2026-02-25 13:36:44

-- Table: products
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` int(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default-product.jpg',
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `products`
INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `stock`, `created_at`, `updated_at`) VALUES ('5', 'jaket kulit', '100000.00', '0', '', 'default-product.jpg', '9', '2026-02-23 11:42:23', '2026-02-24 15:51:09');
INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `stock`, `created_at`, `updated_at`) VALUES ('6', 'work jacket', '100000.00', '0', '', 'default-product.jpg', '9', '2026-02-23 11:48:56', '2026-02-23 11:50:57');
INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `stock`, `created_at`, `updated_at`) VALUES ('7', 'kaos kaki', '100000.00', '0', '', 'default-product.jpg', '10', '2026-02-24 15:50:41', '2026-02-24 15:50:41');

-- Table: transactions
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `transactions`
INSERT INTO `transactions` (`id`, `user_id`, `product_id`, `quantity`, `total_price`, `status`, `payment_method`, `created_at`, `updated_at`) VALUES ('1', '18', '6', '1', '100000.00', 'pending', 'ewallet', '2026-02-23 11:50:57', '2026-02-23 11:50:57');
INSERT INTO `transactions` (`id`, `user_id`, `product_id`, `quantity`, `total_price`, `status`, `payment_method`, `created_at`, `updated_at`) VALUES ('2', '18', '5', '1', '100000.00', 'pending', 'bank_transfer', '2026-02-24 15:51:09', '2026-02-24 15:51:09');

-- Table: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `users`
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('3', 'admin', 'admin@ecommerce.com', '$2y$10$kCrOqk7qlCv5BQyuGtg2h.oCyNyOqSmPvp79DtRTb2mjwZ.YxaIXK', 'admin', '2026-02-10 16:25:28', '2026-02-10 16:25:28');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('7', 'admin123', 'masudjr@example.com', '$2y$10$3I1dwvtaqALAsbOpIf0t9evqdwjOMIGTarxvUEkNML1zLzfzIBGxS', 'user', '2026-02-11 10:44:48', '2026-02-11 10:44:48');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('11', 'raffa', 'alifadiyaksa1@gmail.com', '$2y$10$OaQwIl8BgrIO8RiucjGgfOJYrGyNhFa38u9cLDOmbpCgd6ezdgbMW', 'user', '2026-02-20 14:03:15', '2026-02-20 14:03:15');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('12', 'polilahot', 'lilahotttt123@gmail.com', '$2y$10$.JEqyYkqbcbElPWKaBxAquXrQNqRmD181ORu8wylyEFmK./QmGnv2', 'user', '2026-02-20 15:11:20', '2026-02-20 15:30:11');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('13', 'budi_santoso', 'budi_santoso@petugas.ecommerce.com', '$2y$10$US9DipR.oXS0hkf8FtslUedU5wFNXkTL9EkPopIB8TcGI/4udW0He', 'petugas', '2026-02-20 15:43:19', '2026-02-20 15:43:19');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('14', 'dadangrumma', 'ddang@gmail.com', '$2y$10$MwvMhnwXK2MVfGRitLMLHuuJnWn4qy41jcWoaAIxLrs.gwEyy4RBS', 'petugas', '2026-02-20 15:43:20', '2026-02-23 10:31:51');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('15', 'joao_felix', 'agus_wijaya@petugas.ecommerce.com', '$2y$10$nlHGxzEbQL9jLq8mnjHlZeQWxP4MHhuh7axHNV1d/RZOXBDnuzeUW', 'petugas', '2026-02-20 15:43:20', '2026-02-20 21:41:01');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('16', 'alif_adiyaksa', 'siealipp@gmail.com', '$2y$10$JkPoAHlLgef6LVvknh9dw.bhcE1NfwfcazWZ2xCy61kGBt8JhNQF6', 'petugas', '2026-02-20 15:43:20', '2026-02-20 22:07:53');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('17', 'alexxbhizzer', 'alexbhizer@gmail.com', '$2y$10$qDXXpUZtt7zNa5xL9Xhvferjx/eXLbd9J8L7bLJJ8Uvs.VrHG2u2.', 'petugas', '2026-02-20 15:43:20', '2026-02-20 22:07:05');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('18', 'raffar', 'raffaraditya0512@gmail.com', '$2y$10$mZyrREAlUzvdzQZiPVUfjuHoR5YZDHtn/AUuffuUjzarlBTdTNOZe', 'user', '2026-02-20 21:02:32', '2026-02-20 21:02:32');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('19', 'popo_cees', 'popo122@gmail.com', '$2y$10$df2TYLgydG8gpoy5e6FN0ukAV4mPoTYJMnRj5wsfUxIZ5zp16O/Eu', 'admin', '2026-02-21 11:07:44', '2026-02-23 10:30:59');

