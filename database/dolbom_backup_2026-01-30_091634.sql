-- Hangbok77 데이터베이스 백업
-- 생성일시: 2026-01-30 09:16:34

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT=0;
START TRANSACTION;

-- 테이블 구조: admins
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(50) NOT NULL COMMENT '로그인 ID',
  `password_hash` varchar(255) NOT NULL COMMENT 'bcrypt',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 데이터: admins
LOCK TABLES `admins` WRITE;
INSERT INTO `admins` (`id`, `admin_id`, `password_hash`, `created_at`) VALUES ('1', 'admin', '$2y$10$22AKVIZESSgZY3vosfq97ONH.rFPU1cVeXNqS013RIUydko8mu86a', '2026-01-30 01:37:03');
UNLOCK TABLES;

-- 테이블 구조: applications
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` char(36) NOT NULL,
  `request_id` char(36) NOT NULL,
  `manager_id` int(10) unsigned NOT NULL,
  `status` enum('PENDING','ACCEPTED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_applications_request_manager` (`request_id`,`manager_id`),
  KEY `idx_applications_request` (`request_id`),
  KEY `idx_applications_manager` (`manager_id`),
  KEY `idx_applications_status` (`status`),
  CONSTRAINT `fk_applications_manager` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_applications_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 데이터: applications
LOCK TABLES `applications` WRITE;
INSERT INTO `applications` (`id`, `request_id`, `manager_id`, `status`, `message`, `created_at`) VALUES ('ac6545e1-15ad-4fff-b3d4-3dfd506d48e6', '81cba645-59e7-401d-bacd-ec97290145b6', '4', 'PENDING', NULL, '2026-01-30 11:06:47');
INSERT INTO `applications` (`id`, `request_id`, `manager_id`, `status`, `message`, `created_at`) VALUES ('ed12ee6d-6f0a-45fa-9945-0caaf5d9fe4d', 'fdaf304b-8817-45ca-ae5a-72ff744297b8', '4', 'PENDING', NULL, '2026-01-30 12:38:32');
INSERT INTO `applications` (`id`, `request_id`, `manager_id`, `status`, `message`, `created_at`) VALUES ('f6998a11-cdca-437e-824b-3a5a856f54bf', 'ad733255-0f47-49fb-b87e-0b8e450f7d8b', '4', 'PENDING', NULL, '2026-01-30 10:35:46');
UNLOCK TABLES;

-- 테이블 구조: billing_keys
DROP TABLE IF EXISTS `billing_keys`;
CREATE TABLE `billing_keys` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `billing_key` varchar(500) NOT NULL,
  `card_company` varchar(50) NOT NULL,
  `card_number_last4` varchar(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_billing_keys_user` (`user_id`),
  CONSTRAINT `fk_billing_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 구조: bookings
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` char(36) NOT NULL,
  `request_id` char(36) NOT NULL,
  `manager_id` int(10) unsigned NOT NULL,
  `actual_start_time` timestamp NULL DEFAULT NULL,
  `actual_end_time` timestamp NULL DEFAULT NULL,
  `final_price` int(10) unsigned NOT NULL,
  `payment_status` enum('PENDING','PAID','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bookings_request` (`request_id`),
  KEY `idx_bookings_manager` (`manager_id`),
  KEY `idx_bookings_payment_status` (`payment_status`),
  CONSTRAINT `fk_bookings_manager` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 구조: manager_device_tokens
DROP TABLE IF EXISTS `manager_device_tokens`;
CREATE TABLE `manager_device_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `manager_id` int(10) unsigned NOT NULL COMMENT '매니저 ID',
  `device_token` varchar(255) NOT NULL COMMENT 'FCM 디바이스 토큰',
  `platform` enum('android','ios','web') NOT NULL DEFAULT 'android' COMMENT '플랫폼',
  `app_version` varchar(50) DEFAULT NULL COMMENT '앱 버전',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '마지막 사용 일시',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_manager_device_token` (`manager_id`,`device_token`),
  KEY `idx_manager_device_manager` (`manager_id`),
  KEY `idx_manager_device_active` (`is_active`),
  KEY `idx_manager_device_token` (`device_token`),
  CONSTRAINT `fk_manager_device_tokens_manager` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='매니저 디바이스 토큰';

-- 테이블 구조: manager_profiles
DROP TABLE IF EXISTS `manager_profiles`;
CREATE TABLE `manager_profiles` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `bio` text DEFAULT NULL,
  `service_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '["서울 강남구","서울 서초구"]' CHECK (json_valid(`service_areas`)),
  `available_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '["병원동행","노인돌봄"]' CHECK (json_valid(`available_services`)),
  `hourly_rate` int(10) unsigned DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `review_count` int(10) unsigned NOT NULL DEFAULT 0,
  `total_bookings` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_manager_profiles_user_id` (`user_id`),
  KEY `idx_manager_profiles_is_active` (`is_active`),
  KEY `idx_manager_profiles_lat_lng` (`lat`,`lng`),
  CONSTRAINT `fk_manager_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 구조: managers
DROP TABLE IF EXISTS `managers`;
CREATE TABLE `managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '이름',
  `ssn` varchar(20) NOT NULL COMMENT '주민번호',
  `phone` varchar(20) NOT NULL COMMENT '전화번호',
  `address1` varchar(255) NOT NULL COMMENT '주소1',
  `address2` varchar(255) DEFAULT NULL COMMENT '주소2',
  `account_number` varchar(50) NOT NULL COMMENT '계좌번호',
  `bank` varchar(50) DEFAULT NULL COMMENT '은행명',
  `specialty` varchar(255) DEFAULT NULL COMMENT '특기',
  `password_hash` varchar(255) NOT NULL COMMENT 'bcrypt',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `photo` varchar(255) DEFAULT NULL COMMENT '프로필 사진 경로',
  `gender` enum('M','F') DEFAULT NULL COMMENT '성별: M=남, F=여',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_managers_ssn` (`ssn`),
  KEY `idx_managers_phone` (`phone`),
  KEY `idx_managers_name` (`name`),
  KEY `idx_managers_gender` (`gender`),
  KEY `idx_managers_bank` (`bank`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 데이터: managers
LOCK TABLES `managers` WRITE;
INSERT INTO `managers` (`id`, `name`, `ssn`, `phone`, `address1`, `address2`, `account_number`, `bank`, `specialty`, `password_hash`, `created_at`, `updated_at`, `photo`, `gender`) VALUES ('1', '홍길동', '123456-1234567', '010-1234-5678', '서울시 강남구 테헤란로 123', '101동 101호', '123-456-789012', NULL, NULL, '', '2026-01-30 01:47:31', '2026-01-30 01:47:31', NULL, NULL);
INSERT INTO `managers` (`id`, `name`, `ssn`, `phone`, `address1`, `address2`, `account_number`, `bank`, `specialty`, `password_hash`, `created_at`, `updated_at`, `photo`, `gender`) VALUES ('2', '김철수', '234567-2345678', '010-2345-6789', '경기도 성남시 분당구 정자동 456', '202동 303호', '234-567-890123', NULL, NULL, '', '2026-01-30 01:47:31', '2026-01-30 01:47:31', NULL, NULL);
INSERT INTO `managers` (`id`, `name`, `ssn`, `phone`, `address1`, `address2`, `account_number`, `bank`, `specialty`, `password_hash`, `created_at`, `updated_at`, `photo`, `gender`) VALUES ('3', '이영희', '345678-3456789', '010-3456-7890', '인천시 남동구 구월동 789', '301동 405호', '345-678-901234', NULL, NULL, '', '2026-01-30 01:47:31', '2026-01-30 01:47:31', NULL, NULL);
INSERT INTO `managers` (`id`, `name`, `ssn`, `phone`, `address1`, `address2`, `account_number`, `bank`, `specialty`, `password_hash`, `created_at`, `updated_at`, `photo`, `gender`) VALUES ('4', '이강석', '7005041024620', '01034061921', '공세동', '상떼레이크뷰 104-1201', '123522455555521', '부산은행', '요리', '$2y$10$AJ2P1NBCPJdSrX7XTYIAZOIzBzMERd6wQd3DJmlPZbYjTLsjnhWiC', '2026-01-30 10:32:04', '2026-01-30 15:18:59', NULL, NULL);
UNLOCK TABLES;

-- 테이블 구조: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_created` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 구조: payments
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` char(36) NOT NULL,
  `booking_id` char(36) DEFAULT NULL,
  `service_request_id` char(36) DEFAULT NULL,
  `customer_id` char(36) NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_key` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','SUCCESS','FAILED','CANCELLED','PARTIAL_REFUNDED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `failed_reason` text DEFAULT NULL,
  `refund_amount` int(10) unsigned DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payments_booking` (`booking_id`),
  KEY `idx_payments_customer` (`customer_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_paid_at` (`paid_at`),
  KEY `idx_payments_service_request` (`service_request_id`),
  KEY `idx_payments_refunded_at` (`refunded_at`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_service_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 데이터: payments
LOCK TABLES `payments` WRITE;
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('01ef7276-d5f7-4812-867b-0f0fc8e7254d', NULL, 'fda1d600-03c8-4fd4-8bf3-d7bb8dd9f56d', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'REFUNDED', NULL, '10000', '[2026-01-30 04:23] 10,000원: 개인사정', '2026-01-30 12:23:08', '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('0ff76430-c4c6-49ba-8cac-369179375674', NULL, '2ccac519-e5f5-4c77-8279-9595e3b75ca0', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('21b7b848-7422-4385-90de-f237a781e54b', NULL, '81f2ee2e-d265-4335-abd3-1bae82e952c3', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'PARTIAL_REFUNDED', NULL, '3000', '[2026-01-30 04:23] 3,000원: RODLSTK', '2026-01-30 12:23:37', '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('2c884c46-d382-4517-83c1-47c5f496b37d', NULL, '2bd1c9a2-878c-47e2-b09e-a76560cb98de', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('3f3554b9-24e7-42f2-988d-7965088728b2', NULL, '4f53404b-76c3-4169-b53c-42e65c4eebd3', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '20000', '카드', 'tgen_20260130123215uaHW4', 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 12:32:42', '2026-01-30 12:32:42');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('4322d085-a8d7-4a02-a6e1-ae9c6c961867', NULL, 'c2c5187c-269a-4115-a4a4-5fa44e0560bf', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('458233dc-cd2c-42ea-bb14-4bb1c81d1b22', NULL, '126215ec-0459-4db4-8254-78828c2a044b', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('48a6d388-1a54-4573-8743-4af2c211f1f5', NULL, 'a05078a4-cba9-4430-a18d-45d80ca96fa4', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('4b925d88-8206-44a5-aeca-6ec78985084d', NULL, 'a7a5bbf7-822b-4815-a617-be8a025a27d7', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('5c33369b-95d1-42e2-a24e-28c84e54d432', NULL, '7a258336-cd1e-4596-8880-918af8447214', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '20000', '카드', 'tgen_20260130111903vCSW8', 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:19:37', '2026-01-30 11:19:37');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('7ac6cb8e-b6c5-4317-857d-a8bcf31d4dec', NULL, '9d484039-e87f-4b47-98c1-b55b6913dd62', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('8630d205-5a8d-475e-95da-f26ebb0dd1d6', NULL, '10376e42-a347-4528-8ba8-2c1eede4fee6', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('86b7239c-482a-427a-be42-5691d792158b', NULL, 'c5a8e886-d4af-4076-9c24-cb3789aeb4f1', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('987ab05e-6660-4170-92f8-cbbf5e334c20', NULL, '3908ea87-9d90-412f-bd2d-94d1e597f650', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('a43f9f3d-76bf-4670-a75d-95c3ea5fc663', NULL, 'fdaf304b-8817-45ca-ae5a-72ff744297b8', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('a5fcefd2-f46e-44f5-b960-14bb66cfb66d', NULL, '3e8a92ef-8d8c-4b5b-8446-8a873b7cf7a6', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('b71fbb75-b2e7-4b1e-b9ef-259f99eca632', NULL, 'f1cc5012-66c2-458b-8694-852bd29c5ee8', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('c820b1d7-5cc8-47bb-9203-fd957c1a96e2', NULL, '2473ff37-c88b-49b3-a1b8-7abbfdc6a162', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('d23da736-e946-4e9b-a380-e5aeff9f52da', NULL, 'e8335fd3-118f-41a7-9ecf-d1d32f79bd4c', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('d3274c53-1a53-4cd0-ac21-d189448e653b', NULL, 'bf29b951-f9e5-4f76-97f3-8be0f4af3bfe', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('d3846826-2ac6-4e9f-986a-f6d15d055f3f', NULL, 'c67427e1-6a8b-46b2-82ff-04439d4f1fe1', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('dc0290ad-f726-4457-b58d-3b43b27e46be', NULL, 'e9c01461-3e8e-4d0d-86f8-46a68c611027', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('e47460cc-16c6-4a88-abb8-6c7d32c0e249', NULL, '81cba645-59e7-401d-bacd-ec97290145b6', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '20000', '카드', 'tgen_20260130011540vg424', 'PARTIAL_REFUNDED', NULL, '5000', '[2026-01-30 02:12] 5,000원: dasdsafdsdsf', '2026-01-30 10:12:53', '2026-01-30 01:15:55', '2026-01-30 01:15:55');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('f4d10441-9747-47c7-accf-14703bfe69d5', NULL, 'ad733255-0f47-49fb-b87e-0b8e450f7d8b', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
INSERT INTO `payments` (`id`, `booking_id`, `service_request_id`, `customer_id`, `amount`, `payment_method`, `payment_key`, `status`, `failed_reason`, `refund_amount`, `refund_reason`, `refunded_at`, `paid_at`, `created_at`) VALUES ('fe98cb66-248f-4fa9-bcce-5e367b1077b7', NULL, '611dc533-cf2f-4981-99db-301efac3b508', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '10000', 'CARD', NULL, 'SUCCESS', NULL, NULL, NULL, NULL, '2026-01-30 11:33:58', '2026-01-30 11:33:58');
UNLOCK TABLES;

-- 테이블 구조: reviews
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` char(36) NOT NULL,
  `booking_id` char(36) NOT NULL,
  `reviewer_id` char(36) NOT NULL,
  `manager_id` char(36) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reviews_booking` (`booking_id`),
  KEY `idx_reviews_manager` (`manager_id`),
  KEY `idx_reviews_rating` (`rating`),
  KEY `fk_reviews_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 구조: service_requests
DROP TABLE IF EXISTS `service_requests`;
CREATE TABLE `service_requests` (
  `id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `service_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration_minutes` int(10) unsigned NOT NULL,
  `address` varchar(255) NOT NULL,
  `address_detail` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL COMMENT '고객 연락처',
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('PENDING','MATCHING','CONFIRMED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `estimated_price` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_service_requests_customer` (`customer_id`),
  KEY `idx_service_requests_status` (`status`),
  KEY `idx_service_requests_date` (`service_date`),
  KEY `idx_service_requests_lat_lng` (`lat`,`lng`),
  CONSTRAINT `fk_service_requests_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 데이터: service_requests
LOCK TABLES `service_requests` WRITE;
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('10376e42-a347-4528-8ba8-2c1eede4fee6', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '가사돌봄', '2026-02-10', '18:27:00', '300', '경기도 용인시 기흥구 구갈로 464', NULL, NULL, '37.28020000', '127.16280000', NULL, 'CONFIRMED', '100000', '2026-01-01 03:31:31', '2026-01-30 11:36:19');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('126215ec-0459-4db4-8254-78828c2a044b', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '병원 동행', '2026-02-12', '12:04:00', '300', '인천광역시 연수구 송도과학로 798', NULL, NULL, '37.35250000', '126.62970000', NULL, 'CONFIRMED', '100000', '2026-01-25 03:31:31', '2026-01-30 11:36:19');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('2473ff37-c88b-49b3-a1b8-7abbfdc6a162', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '가사돌봄', '2026-02-07', '17:04:00', '120', '서울특별시 강남구 테헤란로 155', '999호', NULL, '37.52170000', '127.02970000', '편안하게 진행 부탁드립니다', 'CONFIRMED', '40000', '2026-01-09 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('2bd1c9a2-878c-47e2-b09e-a76560cb98de', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '기타', '2026-02-15', '08:17:00', '300', '경기도 수원시 영통구 월드컵로 993', '416호', NULL, '37.26010000', '126.99980000', NULL, 'COMPLETED', '100000', '2026-01-28 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('2ccac519-e5f5-4c77-8279-9595e3b75ca0', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '기타', '2026-02-01', '12:48:00', '180', '서울특별시 송파구 올림픽로 290', '815호', NULL, '37.55230000', '127.14250000', NULL, 'CONFIRMED', '60000', '2026-01-25 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('3908ea87-9d90-412f-bd2d-94d1e597f650', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '생활동행', '2026-02-05', '16:45:00', '180', '인천광역시 연수구 송도과학로 491', NULL, NULL, '37.41420000', '126.65660000', '상세한 설명 부탁드립니다', 'CONFIRMED', '60000', '2026-01-04 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('3e8a92ef-8d8c-4b5b-8446-8a873b7cf7a6', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '생활동행', '2026-02-16', '10:34:00', '60', '경기도 용인시 기흥구 구갈로 908', NULL, NULL, '37.25580000', '127.10350000', '안전하게 진행 부탁드립니다', 'CANCELLED', '20000', '2026-01-09 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('4f53404b-76c3-4169-b53c-42e65c4eebd3', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '가사돌봄', '2026-02-05', '04:00:00', '60', '경기도 용인시 기흥구 구갈로72번길 10 (구갈동)', '123', '01034061921', '37.28058929', '127.11248958', NULL, 'CONFIRMED', '20000', '2026-01-30 12:32:15', '2026-01-30 12:32:42');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('611dc533-cf2f-4981-99db-301efac3b508', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '아이 돌봄', '2026-02-01', '09:43:00', '120', '경기도 성남시 분당구 정자로 598', '978호', NULL, '37.36900000', '127.09250000', NULL, 'COMPLETED', '40000', '2026-01-13 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('7a258336-cd1e-4596-8880-918af8447214', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '병원 동행', '2026-02-03', '01:30:00', '60', '경기도 용인시 기흥구 구갈로72번길 10 (구갈동)', '123', NULL, '37.28058929', '127.11248958', '요리잘함', 'CONFIRMED', '20000', '2026-01-30 11:19:02', '2026-01-30 11:19:37');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('81cba645-59e7-401d-bacd-ec97290145b6', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '가사돌봄', '2026-02-05', '02:30:00', '60', '경기도 용인시 기흥구 구갈로72번길 10 (구갈동)', '123', NULL, '37.28058929', '127.11248958', NULL, 'CANCELLED', '20000', '2026-01-30 01:15:38', '2026-01-30 11:26:27');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('81f2ee2e-d265-4335-abd3-1bae82e952c3', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '노인 돌봄', '2026-02-25', '12:18:00', '300', '서울특별시 송파구 올림픽로 757', '199호', NULL, '37.51430000', '127.12160000', '편안하게 진행 부탁드립니다', 'CANCELLED', '100000', '2026-01-28 03:31:31', '2026-01-30 12:42:01');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('9d484039-e87f-4b47-98c1-b55b6913dd62', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '생활동행', '2026-02-16', '17:05:00', '300', '경기도 수원시 영통구 월드컵로 429', NULL, NULL, '37.22870000', '127.00090000', '상세한 설명 부탁드립니다', 'CONFIRMED', '100000', '2026-01-22 03:31:31', '2026-01-30 11:36:19');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('a05078a4-cba9-4430-a18d-45d80ca96fa4', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '가사돌봄', '2026-02-01', '12:52:00', '180', '경기도 용인시 기흥구 구갈로 240', '734호', NULL, '37.27270000', '127.10570000', '시간 엄수 부탁드립니다', 'CONFIRMED', '60000', '2026-01-15 03:31:31', '2026-01-30 11:36:19');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('a7a5bbf7-822b-4815-a617-be8a025a27d7', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '아이 돌봄', '2026-02-27', '09:00:00', '60', '경기도 성남시 분당구 정자로 111', '625호', NULL, '37.32340000', '127.08180000', NULL, 'COMPLETED', '20000', '2026-01-30 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('ad733255-0f47-49fb-b87e-0b8e450f7d8b', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '병원 동행', '2026-02-04', '04:00:00', '60', '경기도 용인시 기흥구 구갈로72번길 10 (구갈동)', '123', NULL, '37.28058929', '127.11248958', NULL, 'MATCHING', '20000', '2026-01-30 01:14:10', '2026-01-30 11:41:40');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('bf29b951-f9e5-4f76-97f3-8be0f4af3bfe', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '가사돌봄', '2026-02-05', '03:00:00', '60', '경기도 용인시 기흥구 구갈로72번길 10 (구갈동)', '123', NULL, '37.28058929', '127.11248958', '{\"payment\":{\"payment_key\":\"tgen_20260130010634sNbr5\",\"payment_method\":\"카드\",\"amount\":20000,\"paid_at\":\"2026-01-29 17:06:49\"}}', 'CANCELLED', '20000', '2026-01-30 01:06:32', '2026-01-30 01:19:48');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('c2c5187c-269a-4115-a4a4-5fa44e0560bf', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '생활동행', '2026-02-21', '10:38:00', '300', '서울특별시 서초구 서초대로 354', '620호', NULL, '37.51290000', '126.99180000', '친절하게 부탁드립니다', 'CONFIRMED', '100000', '2026-01-06 03:31:31', '2026-01-30 11:36:19');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('c5a8e886-d4af-4076-9c24-cb3789aeb4f1', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '생활동행', '2026-02-09', '18:45:00', '180', '서울특별시 강남구 테헤란로 568', '229호', NULL, '37.50230000', '127.05760000', NULL, 'CANCELLED', '60000', '2026-01-08 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('c67427e1-6a8b-46b2-82ff-04439d4f1fe1', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '병원 동행', '2026-02-25', '10:45:00', '240', '경기도 수원시 영통구 월드컵로 629', NULL, NULL, '37.28000000', '127.03510000', NULL, 'COMPLETED', '80000', '2026-01-11 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('e8335fd3-118f-41a7-9ecf-d1d32f79bd4c', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '기타', '2026-02-11', '14:00:00', '180', '경기도 성남시 분당구 정자로 807', NULL, NULL, '37.32310000', '127.11980000', NULL, 'CONFIRMED', '60000', '2026-01-18 03:31:31', '2026-01-30 11:36:19');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('e9c01461-3e8e-4d0d-86f8-46a68c611027', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '기타', '2026-02-11', '11:11:00', '240', '서울특별시 송파구 올림픽로 532', NULL, NULL, '37.50300000', '127.12960000', '친절하게 부탁드립니다', 'CONFIRMED', '80000', '2026-01-02 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('f1cc5012-66c2-458b-8694-852bd29c5ee8', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '병원 동행', '2026-02-03', '08:51:00', '240', '인천광역시 연수구 송도과학로 289', '482호', NULL, '37.40040000', '126.63360000', NULL, 'COMPLETED', '80000', '2026-01-12 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('fda1d600-03c8-4fd4-8bf3-d7bb8dd9f56d', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '기타', '2026-01-31', '17:08:00', '300', '부산광역시 해운대구 해운대해변로 813', NULL, NULL, '35.11790000', '129.15670000', NULL, 'CANCELLED', '100000', '2026-01-25 03:31:31', '2026-01-30 11:31:31');
INSERT INTO `service_requests` (`id`, `customer_id`, `service_type`, `service_date`, `start_time`, `duration_minutes`, `address`, `address_detail`, `phone`, `lat`, `lng`, `details`, `status`, `estimated_price`, `created_at`, `updated_at`) VALUES ('fdaf304b-8817-45ca-ae5a-72ff744297b8', '569ba86b-713c-40d7-a196-5a21c66bc9cd', '생활동행', '2026-02-02', '19:45:00', '60', '경기도 성남시 분당구 정자로 763', NULL, NULL, '37.40900000', '127.09500000', '안전하게 진행 부탁드립니다', 'MATCHING', '20000', '2026-01-03 03:31:31', '2026-01-30 12:38:32');
UNLOCK TABLES;

-- 테이블 구조: settlements
DROP TABLE IF EXISTS `settlements`;
CREATE TABLE `settlements` (
  `id` char(36) NOT NULL,
  `manager_id` char(36) NOT NULL,
  `booking_id` char(36) NOT NULL,
  `gross_amount` int(10) unsigned NOT NULL,
  `platform_fee` int(10) unsigned NOT NULL,
  `platform_fee_rate` decimal(5,4) NOT NULL,
  `net_amount` int(10) unsigned NOT NULL,
  `status` enum('PENDING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `bank_name` varchar(50) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_settlements_manager` (`manager_id`),
  KEY `idx_settlements_status` (`status`),
  KEY `idx_settlements_created` (`created_at`),
  KEY `fk_settlements_booking` (`booking_id`),
  CONSTRAINT `fk_settlements_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_settlements_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 구조: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address_detail` varchar(255) DEFAULT NULL,
  `role` enum('CUSTOMER','MANAGER','ADMIN') NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `profile_image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_phone` (`phone`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테이블 데이터: users
LOCK TABLES `users` WRITE;
INSERT INTO `users` (`id`, `email`, `password_hash`, `name`, `phone`, `address`, `address_detail`, `role`, `is_verified`, `is_active`, `profile_image_url`, `created_at`, `updated_at`, `last_login_at`) VALUES ('569ba86b-713c-40d7-a196-5a21c66bc9cd', 'realks22@naver.com', '$2y$10$NsfR3tzbsTRHqn1R1JLiLOHJOS31qCJolPgbiI5.F8E4sSNPsrdXe', '이강석', '01034061921', NULL, NULL, 'CUSTOMER', '0', '1', NULL, '2026-01-29 23:16:05', '2026-01-30 15:06:55', '2026-01-30 15:06:55');
UNLOCK TABLES;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
