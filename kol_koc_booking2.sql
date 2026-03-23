-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 17, 2025 lúc 01:41 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: kol_koc_booking2
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand_id` int(10) UNSIGNED NOT NULL,
  `kol_koc_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','declined','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `deadline` date NOT NULL,
  `posts` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `brand_id`, `kol_koc_id`, `service_id`, `status`, `created_at`, `payment_status`, `deadline`, `posts`) VALUES
(26, 19, 22, 24, 'completed', '2025-05-17 04:48:32', 'pending', '2025-05-19', 1),
(27, 13, 22, 24, 'completed', '2025-05-17 06:57:02', 'pending', '2025-05-22', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `image`, `position`) VALUES
(3, 'Food', 'food.jpg', 0),
(4, 'Travel', 'travel.jpg', 0),
(66, 'Fasion', NULL, 2),
(67, 'Làm Đẹp', NULL, 3);

--
-- Bẫy `categories`
--
DELIMITER $$
CREATE TRIGGER `before_category_insert` BEFORE INSERT ON `categories` FOR EACH ROW BEGIN
    IF NEW.position = 0 THEN
        SET NEW.position = (SELECT COALESCE(MAX(position), 0) + 1 FROM categories);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','paid','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `status`, `created_at`) VALUES
(26, 26, 20000000.00, 'bank_transfer', 'pending', '2025-05-17 04:48:32'),
(27, 27, 20000000.00, 'bank_transfer', 'paid', '2025-05-17 06:57:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `portfolios`
--

CREATE TABLE `portfolios` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `profiles`
--

CREATE TABLE `profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `social_links` varchar(255) DEFAULT NULL,
  `followers` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `profiles`
--

INSERT INTO `profiles` (`user_id`, `name`, `avatar`, `bio`, `industry`, `social_links`, `followers`) VALUES
(13, 'taingunhutrau@gmail.com', '6828719c10553.jpg', 'he', NULL, '', 30),
(19, 'Gucci', '68281f2895f75.png', 'Sang nha', NULL, 'https://www.facebook.com/photo?fbid=850999620559385&set=a.391159133210105', 30),
(22, 'Quan Không Gờ', '682814b07cf9d.jpg', 'Mình là Quan Ko Gờ', NULL, '', 2000000),
(23, 'letuankhang@gmail.com', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `reviewer_id` int(10) UNSIGNED NOT NULL,
  `target_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `services`
--

INSERT INTO `services` (`id`, `user_id`, `name`, `rate`, `description`) VALUES
(23, 22, 'Review Đồ Ăn', 2000000.00, 'Review hay nha'),
(24, 22, 'Review Quần Áo', 20000000.00, 'Tôi Riview quần áo xịn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('text','image','json') NOT NULL DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `type`, `created_at`, `updated_at`) VALUES
('blog_content', 'Blog Content', '<p>Cuộc Sống Đằng Sau M&agrave;n H&igrave;nh: Một Ng&agrave;y Của TikToker T&agrave;i</p>\r\n\r\n<p>TikTok đ&atilde; trở th&agrave;nh một hiện tượng to&agrave;n cầu, nơi những người s&aacute;ng tạo nội dung, hay c&ograve;n gọi l&agrave; TikToker, c&oacute; thể biến những khoảnh khắc đời thường th&agrave;nh những video triệu view. Nhưng bạn c&oacute; bao giờ tự hỏi một ng&agrave;y của một TikToker tr&ocirc;ng như thế n&agrave;o chưa? H&atilde;y c&ugrave;ng kh&aacute;m ph&aacute; một h&agrave;nh tr&igrave;nh ngẫu nhi&ecirc;n trong cuộc sống của một TikToker nh&eacute;!</p>\r\n\r\n<p>S&aacute;ng: &Yacute; Tưởng L&ecirc;n Ng&ocirc;i</p>\r\n\r\n<p>Mỗi ng&agrave;y của một TikToker thường bắt đầu bằng một t&aacute;ch c&agrave; ph&ecirc; v&agrave;... lướt TikTok! Kh&ocirc;ng chỉ để giải tr&iacute;, họ c&ograve;n t&igrave;m kiếm cảm hứng từ c&aacute;c xu hướng mới. Một TikToker c&oacute; thể d&agrave;nh cả tiếng đồng hồ để xem c&aacute;c video thịnh h&agrave;nh, ph&acirc;n t&iacute;ch c&aacute;c điệu nhảy, hiệu ứng, hay thử th&aacute;ch đang &quot;hot&quot;. Sau đ&oacute;, họ sẽ brainstorm &yacute; tưởng cho video của ri&ecirc;ng m&igrave;nh. C&oacute; thể l&agrave; một video h&agrave;i hước về chuyện đi chợ, một điệu nhảy mới, hay thậm ch&iacute; l&agrave; chia sẻ mẹo vặt đời sống.</p>\r\n\r\n<p>&quot;&Yacute; tưởng hay nhất thường đến khi đang tắm hoặc l&uacute;c chuẩn bị đi ngủ!&quot; - Một TikToker từng chia sẻ.</p>\r\n\r\n<p>Trưa: Sản Xuất Nội Dung</p>\r\n\r\n<p>Đến trưa, TikToker sẽ bắt tay v&agrave;o quay video. Đừng nghĩ rằng chỉ cần cầm điện thoại l&ecirc;n l&agrave; xong! Họ cần chuẩn bị &aacute;nh s&aacute;ng, g&oacute;c quay, v&agrave; đ&ocirc;i khi l&agrave; cả trang phục ph&ugrave; hợp. Một số TikToker c&ograve;n đầu tư v&agrave;o micro, đ&egrave;n ring light, v&agrave; thậm ch&iacute; l&agrave; phần mềm chỉnh sửa để video tr&ocirc;ng chuy&ecirc;n nghiệp hơn.</p>\r\n\r\n<p>Quay một video 15 gi&acirc;y c&oacute; thể mất cả tiếng v&igrave; phải l&agrave;m đi l&agrave;m lại để đạt được sự ho&agrave;n hảo. Chưa kể, nếu video c&oacute; yếu tố đồng diễn hay cần bạn b&egrave; hỗ trợ, lịch tr&igrave;nh c&agrave;ng phức tạp hơn.</p>\r\n\r\n<p>Chiều: Chỉnh Sửa v&agrave; Đăng Tải</p>\r\n\r\n<p>Sau khi quay xong, TikToker d&agrave;nh thời gian để chỉnh sửa video. Họ th&ecirc;m hiệu ứng, lồng nhạc, hoặc ch&egrave;n chữ để tăng t&iacute;nh hấp dẫn. Một số người c&ograve;n tỉ mỉ kiểm tra từng khung h&igrave;nh để đảm bảo video kh&ocirc;ng c&oacute; lỗi.</p>\r\n\r\n<p>Khi đ&atilde; h&agrave;i l&ograve;ng, họ chọn thời điểm &quot;v&agrave;ng&quot; để đăng tải, thường l&agrave; v&agrave;o giờ m&agrave; kh&aacute;n giả của họ hoạt động nhiều nhất (thường l&agrave; buổi tối). Caption hấp dẫn v&agrave; hashtag ph&ugrave; hợp cũng l&agrave; yếu tố quan trọng để video tiếp cận được nhiều người hơn.</p>\r\n\r\n<p>Tối: Tương T&aacute;c v&agrave; Kết Nối</p>\r\n\r\n<p>Sau khi đăng video, TikToker kh&ocirc;ng chỉ ngồi chờ view m&agrave; c&ograve;n t&iacute;ch cực tương t&aacute;c với người xem. Họ trả lời b&igrave;nh luận, cảm ơn những lời khen, v&agrave; đ&ocirc;i khi c&ograve;n tranh thủ quay video &quot;reaction&quot; để đ&aacute;p lại những c&acirc;u hỏi th&uacute; vị từ fan. Đ&acirc;y cũng l&agrave; l&uacute;c họ kết nối với c&aacute;c TikToker kh&aacute;c, trao đổi &yacute; tưởng hoặc thậm ch&iacute; l&ecirc;n kế hoạch collab.</p>\r\n\r\n<p>Đ&ecirc;m: Nghỉ Ngơi Hay... L&ecirc;n &Yacute; Tưởng Mới?</p>\r\n\r\n<p>Khi đ&ecirc;m đến, một số TikToker nghỉ ngơi để nạp lại năng lượng, nhưng nhiều người lại tiếp tục lướt TikTok để t&igrave;m cảm hứng. Một &yacute; tưởng bất chợt c&oacute; thể khiến họ bật dậy, ghi ch&uacute; nhanh v&agrave;o điện thoại để quay v&agrave;o ng&agrave;y h&ocirc;m sau.</p>\r\n\r\n<p>Kết Luận</p>\r\n\r\n<p>L&agrave;m TikToker kh&ocirc;ng chỉ đơn giản l&agrave; quay video v&agrave; đăng l&ecirc;n. Đ&oacute; l&agrave; sự kết hợp giữa s&aacute;ng tạo, ki&ecirc;n nhẫn, v&agrave; khả năng nắm bắt xu hướng. D&ugrave; c&oacute; những ng&agrave;y mệt mỏi, nhưng niềm vui khi thấy video của m&igrave;nh được y&ecirc;u th&iacute;ch ch&iacute;nh l&agrave; động lực để họ tiếp tục. Bạn nghĩ sao về cuộc sống của một TikToker? C&oacute; muốn thử sức một lần kh&ocirc;ng?</p>\r\n', 'text', '2025-05-01 10:49:46', '2025-05-17 07:09:08'),
('contact_address', 'Contact Address', '<p><strong>19 Đ. Nguyễn Hữu Thọ, T&acirc;n Phong, Quận 7, Hồ Ch&iacute; Minh</strong></p>\r\n', 'text', '2025-03-15 05:34:15', '2025-05-17 07:09:08'),
('contact_email', 'Contact Email', 'phantaib52@gmail.com', 'text', '2025-03-15 05:34:15', '2025-05-17 07:09:08'),
('contact_phone', 'Contact Phone', '0989261642', 'text', '2025-03-15 05:34:15', '2025-05-17 07:09:08'),
('homepage_background', 'Homepage Background Image', '6825ccae15693.jpg', 'image', '2025-03-15 05:34:15', '2025-05-15 11:14:54'),
('pinned_kols', 'Pinned KOLs', '[\"11\"]', 'json', '2025-03-15 05:34:15', '2025-05-17 07:09:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','brand','kol_koc','personal') NOT NULL,
  `status` enum('pending','active','locked') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin@example2.com', '$2y$10$mNvOQmER0rsT0Ot0phK6IOnYEhm7rgyN55rhg8ZCt3d.ri1ycSg06', 'admin', 'active', '2025-04-01 06:12:10'),
(13, 'taingunhutrau@gmail.com', '$2y$10$vjIuQMKMauNrpz42JDMaIet.v.qNO5PZkDCwOQroqCocmizLNANL.', 'brand', 'active', '2025-04-24 08:25:31'),
(19, 'taibrand@gmail.com', '$2y$10$YLXkSUwRbedOVhTAtMnyWOXtrunumN3xZbSbGYC.nZltMDOcbHJF.', 'brand', 'active', '2025-05-13 05:59:03'),
(22, 'quankhonggo@gmail.com', '$2y$10$ZIVSlUctzxQenVxpHGR6.uLPIiAPWXgibI2gbH3qb7Qx1Yvh1NBkG', 'kol_koc', 'active', '2025-05-17 04:45:35'),
(23, 'letuankhang@gmail.com', '$2y$10$FPUcXQ8gpuyj9ipWhtK2Ue0toTXHawETii/p0YuOXuuR.tFvkMARi', 'kol_koc', 'pending', '2025-05-17 11:16:12'),
(24, 'phantaib52@gmail.com', '$2y$10$r1LFYdNs0lEejKOnAM.mb./9PChd9P/iLAmU2fwN61aqUDQ8Rtut6', 'admin', 'active', '2025-05-17 11:18:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_categories`
--

CREATE TABLE `user_categories` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `user_categories`
--

INSERT INTO `user_categories` (`user_id`, `category_id`) VALUES
(13, 3),
(13, 66),
(19, 4),
(19, 66),
(22, 3);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `kol_koc_id` (`kol_koc_id`),
  ADD KEY `bookings_ibfk_3` (`service_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categories_position` (`position`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Chỉ mục cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`),
  ADD KEY `idx_token` (`token`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Chỉ mục cho bảng `portfolios`
--
ALTER TABLE `portfolios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `target_id` (`target_id`);

--
-- Chỉ mục cho bảng `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_categories`
--
ALTER TABLE `user_categories`
  ADD PRIMARY KEY (`user_id`,`category_id`),
  ADD KEY `idx_user_categories_user` (`user_id`),
  ADD KEY `idx_user_categories_category` (`category_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `portfolios`
--
ALTER TABLE `portfolios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`kol_koc_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Các ràng buộc cho bảng `portfolios`
--
ALTER TABLE `portfolios`
  ADD CONSTRAINT `portfolios_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_categories`
--
ALTER TABLE `user_categories`
  ADD CONSTRAINT `user_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
