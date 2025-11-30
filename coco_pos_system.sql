-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 08:39 AM
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
-- Database: `coco_pos_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `RestockProduct` (IN `prod_id` INT, IN `qty` INT)   BEGIN
    DECLARE current_stock INT;

    SELECT stock INTO current_stock 
    FROM products 
    WHERE id = prod_id;

    IF current_stock + qty > 1000 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Restock failed: Stock limit of 1000 exceeded.';
    ELSE
        UPDATE products 
        SET stock = stock + qty 
        WHERE id = prod_id;

        INSERT INTO restock_logs (product_id, quantity_added) 
        VALUES (prod_id, qty);
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `stars` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `order_id`, `user_id`, `rating`, `product_id`, `stars`, `comment`, `created_at`) VALUES
(1, 38, 8, 4, 5, 0, 'ang ganda!', '2025-10-22 06:37:33'),
(2, 46, 8, 5, 4, 0, 'solid products!', '2025-10-22 14:16:24'),
(3, 53, 8, 5, 6, 0, 'ang ganda ng product!', '2025-10-23 06:56:18'),
(4, 57, 8, 4, 4, 0, 'w', '2025-10-23 09:25:48'),
(5, 65, 9, 5, 1, 0, 'solid!', '2025-11-05 02:26:47'),
(6, 89, 9, 4, 1, 0, 'soliid!', '2025-11-06 03:23:39'),
(7, 106, 9, 5, 3, 0, '.', '2025-11-20 06:01:11'),
(8, 107, 9, 4, 2, 0, 'w', '2025-11-20 06:02:17'),
(9, 108, 9, 4, 3, 0, 'w', '2025-11-20 06:07:27');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `order_date` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL CHECK (`total` >= 0),
  `status` enum('Pending','Ready for Pick Up','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `shipping_address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash on Pickup',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(50) NOT NULL DEFAULT 'Bliss'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `order_date`, `total`, `status`, `shipping_address`, `contact_number`, `payment_method`, `remarks`, `created_at`, `updated_at`, `location`) VALUES
(5, 2, 65.00, '2025-10-22 01:39:18', 65.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-14 10:55:46', '2025-10-22 13:57:07', 'Bliss'),
(6, 4, 400.00, '2025-10-22 01:39:18', 400.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-15 06:45:06', '2025-10-22 13:57:07', 'Bliss'),
(7, 4, 70.00, '2025-10-22 01:39:18', 70.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-15 07:20:30', '2025-10-22 13:57:07', 'Bliss'),
(8, 4, 65.00, '2025-10-22 01:39:18', 65.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-15 07:24:50', '2025-10-22 13:57:07', 'Bliss'),
(9, 4, 100.00, '2025-10-22 01:39:18', 100.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-15 07:42:37', '2025-10-22 13:57:07', 'Bliss'),
(10, 4, 6500.00, '2025-10-22 01:39:18', 6500.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-15 07:43:23', '2025-10-22 13:57:07', 'Bliss'),
(11, 4, 120.00, '2025-10-22 01:39:18', 120.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-15 08:03:27', '2025-10-22 13:57:07', 'Bliss'),
(12, 4, 200.00, '2025-10-22 01:39:18', 200.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-16 02:01:09', '2025-10-22 13:57:07', 'Bliss'),
(13, 4, 70.00, '2025-10-22 01:39:18', 70.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 01:46:35', '2025-10-22 13:57:07', 'Bliss'),
(14, 5, 70.00, '2025-10-22 01:39:18', 70.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 02:12:17', '2025-10-22 13:57:07', 'Bliss'),
(15, 5, 70.00, '2025-10-22 01:39:18', 70.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 02:14:06', '2025-10-22 13:57:07', 'Bliss'),
(16, 4, 270.00, '2025-10-22 01:39:18', 270.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 04:08:05', '2025-10-22 13:57:07', 'Bliss'),
(17, 4, 370.00, '2025-10-22 01:39:18', 370.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 04:12:19', '2025-10-22 13:57:07', 'Bliss'),
(18, 8, 140.00, '2025-10-22 01:39:18', 140.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 10:06:45', '2025-10-22 13:57:07', 'Bliss'),
(19, 8, 440.00, '2025-10-22 01:39:18', 440.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 11:10:14', '2025-10-22 13:57:07', 'Bliss'),
(20, 8, 130.00, '2025-10-22 01:39:18', 130.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 11:11:50', '2025-10-22 13:57:07', 'Bliss'),
(21, 8, 6305.00, '2025-10-22 01:39:18', 6305.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-20 11:12:13', '2025-10-22 13:57:07', 'Bliss'),
(22, 8, 65.00, '2025-10-22 01:39:18', 65.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 01:49:26', '2025-10-22 13:57:07', 'Bliss'),
(23, 8, 120.00, '2025-10-22 01:39:18', 120.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 01:50:25', '2025-10-22 13:57:07', 'Bliss'),
(24, 8, 3340.00, '2025-10-22 01:39:18', 3340.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 01:54:11', '2025-10-22 13:57:07', 'Bliss'),
(25, 8, 1320.00, '2025-10-22 01:39:18', 1320.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 01:56:54', '2025-10-22 13:57:07', 'Bliss'),
(26, 8, 390.00, '2025-10-22 01:39:18', 390.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:06:32', '2025-10-22 13:57:07', 'Bliss'),
(27, 8, 350.00, '2025-10-22 01:39:18', 350.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:34:57', '2025-10-22 13:57:07', 'Bliss'),
(28, 8, 210.00, '2025-10-22 01:39:18', 210.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:46:23', '2025-10-22 13:57:07', 'Bliss'),
(29, 8, 300.00, '2025-10-22 01:39:18', 300.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:46:37', '2025-10-22 13:57:07', 'Bliss'),
(30, 8, 260.00, '2025-10-22 01:39:18', 260.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:46:51', '2025-10-22 13:57:07', 'Bliss'),
(31, 8, 200.00, '2025-10-22 01:39:18', 200.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:59:05', '2025-10-22 13:57:07', 'Bliss'),
(32, 8, 100.00, '2025-10-22 01:39:18', 100.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 02:59:10', '2025-10-22 13:57:07', 'Bliss'),
(33, 8, 65.00, '2025-10-22 01:39:18', 65.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 03:03:28', '2025-10-22 13:57:07', 'Bliss'),
(34, 8, 65.00, '2025-10-22 01:39:18', 65.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 03:33:19', '2025-10-22 13:57:07', 'Bliss'),
(35, 8, 1000.00, '2025-10-22 01:39:18', 1000.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 04:48:24', '2025-10-22 13:57:07', 'Bliss'),
(36, 8, 70.00, '2025-10-22 01:39:18', 70.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 13:40:03', '2025-10-22 13:57:07', 'Bliss'),
(37, 8, 700.00, '2025-10-22 01:39:18', 700.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-21 13:46:43', '2025-10-22 13:57:07', 'Bliss'),
(38, 8, 65.00, '2025-10-22 01:39:18', 65.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 05:23:35', '2025-10-22 13:57:07', 'Bliss'),
(39, 8, 170.00, '2025-10-22 01:39:18', 170.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 05:41:36', '2025-10-22 13:57:07', 'Bliss'),
(40, 8, 70.00, '2025-10-22 01:39:18', 70.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 06:20:21', '2025-10-22 13:57:07', 'Bliss'),
(41, 8, 70.00, '2025-10-22 01:39:23', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 12:39:23', '2025-10-22 13:37:29', 'Bliss'),
(42, 8, 100.00, '2025-10-22 02:38:24', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 13:38:24', '2025-10-22 13:50:09', 'Bliss'),
(43, 8, 100.00, '2025-10-22 02:40:23', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 13:40:23', '2025-10-22 13:50:06', 'Bliss'),
(44, 8, 140.00, '2025-10-22 02:49:19', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 13:49:19', '2025-10-22 13:50:05', 'Bliss'),
(45, 8, 200.00, '2025-10-22 02:49:26', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 13:49:26', '2025-10-22 13:50:04', 'Bliss'),
(46, 8, 120.00, '2025-10-22 02:59:29', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 13:59:29', '2025-10-22 14:00:10', 'Bliss'),
(47, 8, 65.00, '2025-10-22 02:59:35', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-22 13:59:35', '2025-10-22 14:00:06', 'Bliss'),
(50, 8, 6860.00, '2025-10-22 17:51:02', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 04:51:02', '2025-10-23 04:52:16', 'Bliss'),
(51, 8, 840.00, '2025-10-22 18:15:23', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 05:15:23', '2025-10-23 05:15:41', 'Bliss'),
(52, 8, 70.00, '2025-10-22 18:26:02', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 05:26:02', '2025-10-23 05:26:35', 'Bliss'),
(53, 8, 195.00, '2025-10-22 19:53:21', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 06:53:21', '2025-10-23 06:54:15', 'Bliss'),
(54, 8, 195.00, '2025-10-22 19:53:28', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 06:53:28', '2025-10-23 06:54:13', 'Bliss'),
(55, 8, 200.00, '2025-10-22 19:53:36', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 06:53:36', '2025-10-23 06:54:12', 'Bliss'),
(56, 8, 195.00, '2025-10-22 22:02:21', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 09:02:21', '2025-10-23 09:02:42', 'Bliss'),
(57, 8, 120.00, '2025-10-22 22:21:15', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 09:21:15', '2025-10-23 09:23:30', 'Bliss'),
(58, 8, 200.00, '2025-10-23 06:47:46', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-23 17:47:46', '2025-10-23 17:49:18', 'Bliss'),
(59, 8, 325.00, '2025-10-23 17:41:24', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-10-24 04:41:24', '2025-10-24 04:42:28', 'Bliss'),
(60, 8, 400.00, '2025-10-31 18:21:53', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-01 05:21:53', '2025-11-01 05:22:36', 'Bliss'),
(61, 8, 200.00, '2025-11-03 17:17:19', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-04 04:17:19', '2025-11-04 04:18:14', 'Bliss'),
(62, 8, 140.00, '2025-11-03 23:44:11', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-04 10:44:11', '2025-11-04 11:21:17', 'Bliss'),
(63, 8, 200.00, '2025-11-04 00:20:50', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-04 11:20:50', '2025-11-04 11:21:16', 'Bliss'),
(64, 8, 70.00, '2025-11-04 01:38:47', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-04 12:38:47', '2025-11-04 12:46:35', 'Bliss'),
(65, 9, 200.00, '2025-11-04 01:46:18', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-04 12:46:18', '2025-11-04 12:46:31', 'Bliss'),
(66, 9, 65.00, '2025-11-04 17:05:48', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:05:48', '2025-11-05 04:52:35', 'Bliss'),
(67, 9, 65.00, '2025-11-04 17:06:17', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:06:17', '2025-11-05 04:53:03', 'Bliss'),
(68, 9, 130.00, '2025-11-04 17:07:09', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:07:09', '2025-11-05 04:52:58', 'Bliss'),
(69, 9, 65.00, '2025-11-04 17:09:58', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:09:58', '2025-11-05 04:52:54', 'Bliss'),
(70, 9, 65.00, '2025-11-04 17:12:38', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:12:38', '2025-11-05 04:52:49', 'Bliss'),
(71, 9, 65.00, '2025-11-04 17:29:26', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:29:26', '2025-11-05 04:52:47', 'Bliss'),
(72, 9, 65.00, '2025-11-04 17:29:43', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:29:43', '2025-11-05 04:52:40', 'Bliss'),
(73, 9, 130.00, '2025-11-04 17:30:10', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:30:10', '2025-11-05 04:52:32', 'Bliss'),
(74, 9, 65.00, '2025-11-04 17:37:14', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:37:14', '2025-11-05 04:52:29', 'Bliss'),
(75, 9, 65.00, '2025-11-04 17:38:13', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:38:13', '2025-11-05 04:52:19', 'Bliss'),
(76, 9, 65.00, '2025-11-04 17:38:33', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:38:33', '2025-11-05 04:52:06', 'Bliss'),
(77, 9, 65.00, '2025-11-04 17:38:45', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:38:45', '2025-11-05 04:52:02', 'Bliss'),
(78, 9, 120.00, '2025-11-04 17:43:23', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:43:23', '2025-11-05 04:51:58', 'Bliss'),
(79, 9, 240.00, '2025-11-04 17:44:20', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:44:20', '2025-11-05 04:51:54', 'Bliss'),
(80, 9, 100.00, '2025-11-04 17:45:14', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:45:14', '2025-11-05 04:51:51', 'Bliss'),
(81, 9, 240.00, '2025-11-04 17:50:02', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 04:50:02', '2025-11-05 04:51:47', 'Bliss'),
(82, 9, 120.00, '2025-11-04 18:05:12', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-05 05:05:12', '2025-11-05 05:22:05', 'Bliss'),
(83, 9, 360.00, '2025-11-05 15:08:10', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 02:08:10', '2025-11-06 02:08:27', 'Bliss'),
(84, 9, 240.00, '2025-11-05 15:17:10', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 02:17:10', '2025-11-06 02:17:24', 'Bliss'),
(85, 9, 100.00, '2025-11-05 15:22:34', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 02:22:34', '2025-11-06 02:23:02', 'Bliss'),
(86, 9, 200.00, '2025-11-05 15:25:06', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 02:25:06', '2025-11-06 02:25:21', 'Bliss'),
(87, 9, 200.00, '2025-11-05 16:04:16', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 03:04:16', '2025-11-06 03:04:36', 'Bliss'),
(88, 9, 100.00, '2025-11-05 16:11:54', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 03:11:54', '2025-11-06 03:16:20', 'Bliss'),
(89, 9, 200.00, '2025-11-05 16:15:57', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-06 03:15:57', '2025-11-06 03:16:17', 'Bliss'),
(90, 9, 170.00, '2025-11-13 14:15:17', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-14 01:15:17', '2025-11-14 01:15:53', 'Bliss'),
(91, 9, 120.00, '2025-11-16 15:05:33', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-17 02:05:33', '2025-11-17 02:05:54', 'Bliss'),
(92, 9, 100.00, '2025-11-17 19:20:32', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-18 06:20:32', '2025-11-18 06:21:07', 'Bliss'),
(93, 9, 65.00, '2025-11-17 20:22:28', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-18 07:22:28', '2025-11-18 07:22:44', 'Bliss'),
(94, 9, 65.00, '2025-11-17 20:25:42', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-18 07:25:42', '2025-11-18 07:25:52', 'Bliss'),
(95, 9, 100.00, '2025-11-17 20:59:10', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-18 07:59:10', '2025-11-18 08:02:09', 'Bliss'),
(96, 9, 130.00, '2025-11-17 21:30:49', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-18 08:30:49', '2025-11-18 08:31:06', 'Bliss'),
(97, 9, 200.00, '2025-11-18 14:52:01', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-19 01:52:01', '2025-11-19 01:53:35', 'Bliss'),
(98, 9, 200.00, '2025-11-18 19:12:25', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-19 06:12:25', '2025-11-20 02:18:42', 'Bliss'),
(99, 9, 100.00, '2025-11-19 16:32:03', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 03:32:03', '2025-11-20 03:32:22', 'Bliss'),
(100, 9, 65.00, '2025-11-19 16:45:04', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 03:45:04', '2025-11-20 03:55:01', 'Bliss'),
(101, 9, 65.00, '2025-11-19 16:45:18', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 03:45:18', '2025-11-20 03:54:54', 'Bliss'),
(102, 9, 120.00, '2025-11-19 16:54:26', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 03:54:26', '2025-11-20 03:54:46', 'Bliss'),
(103, 9, 3900.00, '2025-11-19 17:36:08', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 04:36:08', '2025-11-20 04:36:55', 'Bliss'),
(104, 9, 100.00, '2025-11-19 17:40:33', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 04:40:33', '2025-11-20 04:47:25', 'Bliss'),
(105, 9, 100.00, '2025-11-19 18:24:09', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 05:24:09', '2025-11-20 05:24:40', 'Bliss'),
(106, 9, 100.00, '2025-11-19 18:29:30', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 05:29:30', '2025-11-20 05:29:52', 'Bliss'),
(107, 9, 70.00, '2025-11-19 19:01:28', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 06:01:28', '2025-11-20 06:01:56', 'Bliss'),
(108, 9, 100.00, '2025-11-19 19:06:35', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 06:06:35', '2025-11-20 06:06:58', 'Bliss'),
(109, 9, 200.00, '2025-11-19 20:21:03', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 07:21:03', '2025-11-20 07:22:04', 'Bliss'),
(110, 9, 19800.00, '2025-11-19 20:25:30', 0.00, 'Completed', NULL, NULL, 'Cash on Pickup', NULL, '2025-11-20 07:25:30', '2025-11-20 07:26:49', 'Bliss');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `product_name` varchar(100) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `product_name`, `product_image`) VALUES
(1, 5, 6, 1, 65.00, 'Coco Wattles', 'images/coco_wattles.jpg'),
(2, 6, 1, 2, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(3, 7, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(4, 8, 5, 1, 65.00, 'Coco Pot', 'images/coco_pot.jpg'),
(5, 9, 3, 1, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(6, 10, 6, 100, 65.00, 'Coco Wattles', 'images/coco_wattles.jpg'),
(7, 11, 4, 1, 120.00, 'Coco Poles', 'images/coco_poles.jpg'),
(8, 12, 1, 1, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(9, 13, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(10, 14, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(11, 15, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(12, 16, 1, 1, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(13, 16, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(14, 17, 1, 1, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(15, 17, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(16, 17, 3, 1, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(17, 18, 2, 2, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(18, 19, 3, 2, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(19, 19, 4, 2, 120.00, 'Coco Poles', 'images/coco_poles.jpg'),
(20, 20, 5, 2, 65.00, 'Coco Pot', 'images/coco_pot.jpg'),
(21, 21, 5, 97, 65.00, 'Coco Pot', 'images/coco_pot.jpg'),
(22, 22, 6, 1, 65.00, 'Coco Wattles', 'images/coco_wattles.jpg'),
(23, 23, 4, 1, 120.00, 'Coco Poles', 'images/coco_poles.jpg'),
(24, 24, 1, 7, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(25, 24, 2, 6, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(26, 24, 3, 10, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(27, 24, 6, 8, 65.00, 'Coco Wattles', 'images/coco_wattles.jpg'),
(28, 25, 4, 11, 120.00, 'Coco Poles', 'images/coco_poles.jpg'),
(29, 26, 5, 6, 65.00, 'Coco Pot', 'images/coco_pot.jpg'),
(30, 27, 2, 5, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(31, 28, 2, 3, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(32, 29, 3, 3, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(33, 30, 6, 4, 65.00, 'Coco Wattles', 'images/coco_wattles.jpg'),
(34, 31, 1, 1, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(35, 32, 3, 1, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(36, 33, 5, 1, 65.00, 'Coco Pot', 'images/coco_pot.jpg'),
(37, 34, 6, 1, 65.00, 'Coco Wattles', 'images/coco_wattles.jpg'),
(38, 35, 1, 5, 200.00, 'Coco Doormat', 'images/coco_doormat.jpg'),
(39, 36, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(40, 37, 2, 10, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(41, 38, 5, 1, 65.00, 'Coco Pot', 'images/coco_pot.jpg'),
(42, 39, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(43, 39, 3, 1, 100.00, 'Coco Peat', 'images/coco_peat.jpg'),
(44, 40, 2, 1, 70.00, 'Coco Nets', 'images/coco_nets.jpg'),
(45, 41, 2, 1, 70.00, NULL, NULL),
(46, 42, 3, 1, 100.00, NULL, NULL),
(47, 43, 3, 1, 100.00, NULL, NULL),
(48, 44, 2, 2, 70.00, NULL, NULL),
(49, 45, 1, 1, 200.00, NULL, NULL),
(50, 46, 4, 1, 120.00, NULL, NULL),
(51, 47, 6, 1, 65.00, NULL, NULL),
(54, 50, 2, 98, 70.00, NULL, NULL),
(55, 51, 4, 7, 120.00, NULL, NULL),
(56, 52, 2, 1, 70.00, NULL, NULL),
(57, 53, 6, 3, 65.00, NULL, NULL),
(58, 54, 5, 3, 65.00, NULL, NULL),
(59, 55, 3, 2, 100.00, NULL, NULL),
(60, 56, 5, 3, 65.00, NULL, NULL),
(61, 57, 4, 1, 120.00, NULL, NULL),
(62, 58, 1, 1, 200.00, NULL, NULL),
(63, 59, 5, 5, 65.00, NULL, NULL),
(64, 60, 1, 2, 200.00, NULL, NULL),
(65, 61, 1, 1, 200.00, NULL, NULL),
(66, 62, 2, 2, 70.00, NULL, NULL),
(67, 63, 2, 1, 70.00, NULL, NULL),
(68, 63, 6, 2, 65.00, NULL, NULL),
(69, 64, 2, 1, 70.00, NULL, NULL),
(70, 65, 1, 1, 200.00, NULL, NULL),
(71, 66, 5, 1, 65.00, NULL, NULL),
(72, 67, 6, 1, 65.00, NULL, NULL),
(73, 68, 6, 2, 65.00, NULL, NULL),
(74, 69, 5, 1, 65.00, NULL, NULL),
(75, 70, 5, 1, 65.00, NULL, NULL),
(76, 71, 5, 1, 65.00, NULL, NULL),
(77, 72, 5, 1, 65.00, NULL, NULL),
(78, 73, 6, 2, 65.00, NULL, NULL),
(79, 74, 5, 1, 65.00, NULL, NULL),
(80, 75, 6, 1, 65.00, NULL, NULL),
(81, 76, 6, 1, 65.00, NULL, NULL),
(82, 77, 5, 1, 65.00, NULL, NULL),
(83, 78, 4, 1, 120.00, NULL, NULL),
(84, 79, 4, 2, 120.00, NULL, NULL),
(85, 80, 3, 1, 100.00, NULL, NULL),
(86, 81, 4, 2, 120.00, NULL, NULL),
(87, 82, 4, 1, 120.00, NULL, NULL),
(88, 83, 4, 3, 120.00, NULL, NULL),
(89, 84, 4, 2, 120.00, NULL, NULL),
(90, 85, 3, 1, 100.00, NULL, NULL),
(91, 86, 3, 2, 100.00, NULL, NULL),
(92, 87, 1, 1, 200.00, NULL, NULL),
(93, 88, 3, 1, 100.00, NULL, NULL),
(94, 89, 1, 1, 200.00, NULL, NULL),
(95, 90, 2, 1, 70.00, NULL, NULL),
(96, 90, 3, 1, 100.00, NULL, NULL),
(97, 91, 4, 1, 120.00, NULL, NULL),
(98, 92, 3, 1, 100.00, NULL, NULL),
(99, 93, 5, 1, 65.00, NULL, NULL),
(100, 94, 6, 1, 65.00, NULL, NULL),
(101, 95, 3, 1, 100.00, NULL, NULL),
(102, 96, 6, 2, 65.00, NULL, NULL),
(103, 97, 1, 1, 200.00, NULL, NULL),
(104, 98, 1, 1, 200.00, NULL, NULL),
(105, 99, 3, 1, 100.00, NULL, NULL),
(106, 100, 6, 1, 65.00, NULL, NULL),
(107, 101, 5, 1, 65.00, NULL, NULL),
(108, 102, 4, 1, 120.00, NULL, NULL),
(109, 103, 6, 60, 65.00, NULL, NULL),
(110, 104, 3, 1, 100.00, NULL, NULL),
(111, 105, 3, 1, 100.00, NULL, NULL),
(112, 106, 3, 1, 100.00, NULL, NULL),
(113, 107, 2, 1, 70.00, NULL, NULL),
(114, 108, 3, 1, 100.00, NULL, NULL),
(115, 109, 1, 1, 200.00, NULL, NULL),
(116, 110, 1, 99, 200.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `stock` int(11) NOT NULL CHECK (`stock` >= 0 and `stock` <= 1000),
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `image`, `is_featured`, `is_archived`, `created_at`, `updated_at`) VALUES
(1, 'Coco Doormat', 200.00, 260, 'images/coco_doormat.jpg', 1, 0, '2025-10-14 09:15:16', '2025-11-20 07:27:41'),
(2, 'Coco Nets', 70.00, 82, 'images/coco_nets.jpg', 0, 0, '2025-10-14 09:15:16', '2025-11-20 06:01:28'),
(3, 'Coco Peat', 100.00, 105, 'images/coco_peat.jpg', 1, 0, '2025-10-14 09:15:16', '2025-11-20 06:06:35'),
(4, 'Coco Poles', 120.00, 124, 'images/coco_poles.jpg', 0, 0, '2025-10-14 09:15:16', '2025-11-20 03:54:26'),
(5, 'Coco Pot', 65.00, 71, 'images/coco_pot.jpg', 0, 0, '2025-10-14 09:15:16', '2025-11-20 03:45:18'),
(6, 'Coco Wattles', 65.00, 130, 'images/coco_wattles.jpg', 1, 0, '2025-10-14 09:15:16', '2025-11-20 04:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `product_feedback`
--

CREATE TABLE `product_feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stars` int(11) NOT NULL CHECK (`stars` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_feedback`
--

INSERT INTO `product_feedback` (`id`, `user_id`, `product_id`, `stars`, `comment`, `created_at`) VALUES
(1, 8, 1, 5, 'ang ganda solid nung product!', '2025-10-21 18:54:20'),
(2, 8, 1, 5, 'grabe solid nung product!', '2025-10-21 19:00:06'),
(3, 8, 1, 4, 'solid nung product!', '2025-10-21 19:09:39');

-- --------------------------------------------------------

--
-- Table structure for table `restock_logs`
--

CREATE TABLE `restock_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL CHECK (`quantity_added` > 0),
  `restocked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restock_logs`
--

INSERT INTO `restock_logs` (`id`, `product_id`, `quantity_added`, `restocked_at`) VALUES
(1, 5, 10, '2025-10-15 06:39:14'),
(2, 6, 100, '2025-10-15 07:44:01'),
(3, 5, 100, '2025-10-20 11:13:30'),
(4, 1, 40, '2025-10-21 03:59:16'),
(5, 5, 3, '2025-10-21 04:53:04'),
(6, 6, 4, '2025-10-21 04:53:56'),
(7, 5, 4, '2025-10-21 04:54:16'),
(8, 4, 3, '2025-10-21 04:54:28'),
(9, 2, 33, '2025-10-21 13:48:13'),
(10, 2, 100, '2025-10-23 05:13:38'),
(11, 1, 100, '2025-10-23 05:13:47'),
(12, 3, 100, '2025-10-23 05:13:59'),
(13, 4, 100, '2025-10-23 05:14:07'),
(14, 6, 127, '2025-11-20 04:36:41'),
(15, 1, 130, '2025-11-20 07:27:19'),
(16, 1, 130, '2025-11-20 07:27:41');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `order_id`, `product_id`, `quantity`, `total_price`, `sale_date`) VALUES
(1, 18, 2, 2, 140.00, '2025-10-20 15:26:41'),
(2, 27, 2, 5, 350.00, '2025-10-20 15:35:14'),
(3, 30, 6, 4, 260.00, '2025-10-20 15:47:48'),
(4, 29, 3, 3, 300.00, '2025-10-20 15:51:19'),
(5, 28, 2, 3, 210.00, '2025-10-20 15:51:21'),
(6, 32, 3, 1, 100.00, '2025-10-20 15:59:41'),
(7, 31, 1, 1, 200.00, '2025-10-20 15:59:44'),
(8, 33, 5, 1, 65.00, '2025-10-20 16:03:53'),
(9, 17, 1, 1, 200.00, '2025-10-20 16:21:26'),
(10, 17, 2, 1, 70.00, '2025-10-20 16:21:26'),
(11, 17, 3, 1, 100.00, '2025-10-20 16:21:26'),
(12, 16, 1, 1, 200.00, '2025-10-20 16:21:28'),
(13, 16, 2, 1, 70.00, '2025-10-20 16:21:28'),
(14, 15, 2, 1, 70.00, '2025-10-20 16:21:32'),
(15, 14, 2, 1, 70.00, '2025-10-20 16:21:38'),
(16, 13, 2, 1, 70.00, '2025-10-20 16:21:41'),
(17, 5, 6, 1, 65.00, '2025-10-20 16:21:44'),
(18, 12, 1, 1, 200.00, '2025-10-20 16:21:47'),
(19, 11, 4, 1, 120.00, '2025-10-20 16:21:50'),
(20, 10, 6, 100, 6500.00, '2025-10-20 16:21:53'),
(21, 9, 3, 1, 100.00, '2025-10-20 16:21:56'),
(22, 6, 1, 2, 400.00, '2025-10-20 16:21:58'),
(23, 8, 5, 1, 65.00, '2025-10-20 16:22:01'),
(24, 7, 2, 1, 70.00, '2025-10-20 16:22:04'),
(25, 34, 6, 1, 65.00, '2025-10-20 16:33:44'),
(26, 35, 1, 5, 1000.00, '2025-10-20 17:48:38'),
(27, 36, 2, 1, 70.00, '2025-10-21 02:42:20'),
(28, 37, 2, 10, 700.00, '2025-10-21 02:46:58'),
(29, 38, 5, 1, 65.00, '2025-10-21 18:23:53'),
(30, 39, 2, 1, 70.00, '2025-10-21 18:41:52'),
(31, 39, 3, 1, 100.00, '2025-10-21 18:41:52'),
(32, 41, 2, 1, 70.00, '2025-10-22 02:37:29'),
(33, 40, 2, 1, 70.00, '2025-10-22 02:37:33'),
(34, 45, 1, 1, 200.00, '2025-10-22 02:50:04'),
(35, 44, 2, 2, 140.00, '2025-10-22 02:50:05'),
(36, 43, 3, 1, 100.00, '2025-10-22 02:50:06'),
(37, 42, 3, 1, 100.00, '2025-10-22 02:50:09'),
(38, 47, 6, 1, 65.00, '2025-10-22 03:00:06'),
(39, 46, 4, 1, 120.00, '2025-10-22 03:00:10'),
(42, 50, 2, 98, 6860.00, '2025-10-22 17:52:16'),
(43, 51, 4, 7, 840.00, '2025-10-22 18:15:41'),
(44, 52, 2, 1, 70.00, '2025-10-22 18:26:35'),
(45, 55, 3, 2, 200.00, '2025-10-22 19:54:12'),
(46, 54, 5, 3, 195.00, '2025-10-22 19:54:13'),
(47, 53, 6, 3, 195.00, '2025-10-22 19:54:15'),
(48, 56, 5, 3, 195.00, '2025-10-22 22:02:42'),
(49, 57, 4, 1, 120.00, '2025-10-22 22:23:30'),
(50, 58, 1, 1, 200.00, '2025-10-23 06:49:18'),
(51, 59, 5, 5, 325.00, '2025-10-23 17:42:28'),
(52, 60, 1, 2, 400.00, '2025-10-31 18:22:36'),
(53, 61, 1, 1, 200.00, '2025-11-03 17:18:14'),
(54, 63, 2, 1, 70.00, '2025-11-04 00:21:16'),
(55, 63, 6, 2, 130.00, '2025-11-04 00:21:16'),
(56, 62, 2, 2, 140.00, '2025-11-04 00:21:17'),
(57, 65, 1, 1, 200.00, '2025-11-04 01:46:31'),
(58, 64, 2, 1, 70.00, '2025-11-04 01:46:35'),
(59, 81, 4, 2, 240.00, '2025-11-04 17:51:47'),
(60, 80, 3, 1, 100.00, '2025-11-04 17:51:51'),
(61, 79, 4, 2, 240.00, '2025-11-04 17:51:54'),
(62, 78, 4, 1, 120.00, '2025-11-04 17:51:58'),
(63, 77, 5, 1, 65.00, '2025-11-04 17:52:02'),
(64, 76, 6, 1, 65.00, '2025-11-04 17:52:06'),
(65, 75, 6, 1, 65.00, '2025-11-04 17:52:19'),
(66, 74, 5, 1, 65.00, '2025-11-04 17:52:29'),
(67, 73, 6, 2, 130.00, '2025-11-04 17:52:32'),
(68, 66, 5, 1, 65.00, '2025-11-04 17:52:35'),
(69, 72, 5, 1, 65.00, '2025-11-04 17:52:40'),
(70, 71, 5, 1, 65.00, '2025-11-04 17:52:47'),
(71, 70, 5, 1, 65.00, '2025-11-04 17:52:49'),
(72, 69, 5, 1, 65.00, '2025-11-04 17:52:54'),
(73, 68, 6, 2, 130.00, '2025-11-04 17:52:58'),
(74, 67, 6, 1, 65.00, '2025-11-04 17:53:03'),
(75, 82, 4, 1, 120.00, '2025-11-04 18:22:05'),
(76, 83, 4, 3, 360.00, '2025-11-05 15:08:27'),
(77, 84, 4, 2, 240.00, '2025-11-05 15:17:24'),
(78, 85, 3, 1, 100.00, '2025-11-05 15:23:02'),
(79, 86, 3, 2, 200.00, '2025-11-05 15:25:21'),
(80, 87, 1, 1, 200.00, '2025-11-05 16:04:36'),
(81, 89, 1, 1, 200.00, '2025-11-05 16:16:17'),
(82, 88, 3, 1, 100.00, '2025-11-05 16:16:20'),
(83, 90, 2, 1, 70.00, '2025-11-13 14:15:53'),
(84, 90, 3, 1, 100.00, '2025-11-13 14:15:53'),
(85, 91, 4, 1, 120.00, '2025-11-16 15:05:54'),
(86, 92, 3, 1, 100.00, '2025-11-17 19:21:07'),
(87, 93, 5, 1, 65.00, '2025-11-17 20:22:44'),
(88, 94, 6, 1, 65.00, '2025-11-17 20:25:52'),
(89, 95, 3, 1, 100.00, '2025-11-17 21:02:09'),
(90, 96, 6, 2, 130.00, '2025-11-17 21:31:06'),
(91, 97, 1, 1, 200.00, '2025-11-18 14:53:35'),
(92, 98, 1, 1, 200.00, '2025-11-19 15:18:42'),
(93, 99, 3, 1, 100.00, '2025-11-19 16:32:22'),
(94, 102, 4, 1, 120.00, '2025-11-19 16:54:46'),
(95, 101, 5, 1, 65.00, '2025-11-19 16:54:54'),
(96, 100, 6, 1, 65.00, '2025-11-19 16:55:01'),
(97, 103, 6, 60, 3900.00, '2025-11-19 17:36:55'),
(98, 104, 3, 1, 100.00, '2025-11-19 17:47:25'),
(99, 105, 3, 1, 100.00, '2025-11-19 18:24:40'),
(100, 106, 3, 1, 100.00, '2025-11-19 18:29:53'),
(101, 107, 2, 1, 70.00, '2025-11-19 19:01:56'),
(102, 108, 3, 1, 100.00, '2025-11-19 19:06:58'),
(103, 109, 1, 1, 200.00, '2025-11-19 20:22:04'),
(104, 110, 1, 99, 19800.00, '2025-11-19 20:26:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cp_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `surname`, `username`, `password`, `cp_number`, `address`, `role`, `status`, `created_at`, `email`) VALUES
(1, NULL, NULL, 'admin', 'adminadmin', '09500811801', NULL, 'admin', 'active', '2025-10-14 09:15:16', 'admin@example.com'),
(2, NULL, NULL, 'customer1', 'cust123', '09171234567', NULL, 'customer', 'active', '2025-10-14 09:15:16', 'customer1@example.com'),
(3, NULL, NULL, 'jemar0', 'jemar1', '09095548683', 'Bulan, Sorosogon', 'customer', 'active', '2025-10-15 06:09:46', 'jemar0@example.com'),
(4, NULL, NULL, 'jemarrr', 'jemar1', '09095548683', 'Bulan, Sorsogon', 'customer', 'active', '2025-10-15 06:10:29', 'jemarrr@example.com'),
(5, NULL, NULL, 'jemar7', 'jemarjemar', '09095548686', 'Bulan, Sorsogon', 'customer', 'active', '2025-10-20 02:09:21', 'jemar7@example.com'),
(8, NULL, NULL, 'Jemar Graspela', 'jemarrr', '09095548688', 'Bulan, Sorosogon', 'customer', 'active', '2025-10-20 09:41:06', 'Jemar Graspela@example.com'),
(9, NULL, NULL, 'Jemar Hona', 'jemarrr', '09095548688', 'Aquino Bulan, Sorsogon', 'customer', 'active', '2025-11-04 12:41:53', 'jemarhona07@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
--

CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
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
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `product_feedback`
--
ALTER TABLE `product_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restock_logs`
--
ALTER TABLE `restock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_feedback`
--
ALTER TABLE `product_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `restock_logs`
--
ALTER TABLE `restock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restock_logs`
--
ALTER TABLE `restock_logs`
  ADD CONSTRAINT `restock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
