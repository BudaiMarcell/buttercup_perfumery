-- Buttercup Perfumery — adatbázis export
-- MySQL 8.0
-- Karakterkódolás: utf8mb4 / utf8mb4_unicode_ci
--
-- Importálás:
--   mysql -u root -p parfum < database-dump.sql
--
-- Megjegyzés: ez az export a séma + minimális mintaadatokat tartalmazza.
-- A teljes mintaállomány (rendelések, kuponok, kívánságlisták) a seederek
-- futtatásával jön létre:
--   docker compose exec api php artisan migrate:fresh --seed
--

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────────────
-- Felhasználók és authentikáció
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Termékek és kategóriák
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int UNSIGNED NOT NULL DEFAULT '0',
  `volume_ml` smallint UNSIGNED DEFAULT NULL,
  `gender` enum('male','female','unisex') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint UNSIGNED NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Vásárlói fiók (címek, kívánságlista, kártyák)
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `zip_code` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_user_id_foreign` (`user_id`),
  CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE `wishlists` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlists_user_id_product_id_unique` (`user_id`,`product_id`),
  KEY `wishlists_product_id_foreign` (`product_id`),
  CONSTRAINT `wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `brand` varchar(32) NOT NULL,
  `last_four` varchar(4) NOT NULL,
  `exp_month` tinyint UNSIGNED NOT NULL,
  `exp_year` smallint UNSIGNED NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_unique_card` (`user_id`,`last_four`,`exp_month`,`exp_year`),
  KEY `payment_methods_user_id_is_default_index` (`user_id`,`is_default`),
  CONSTRAINT `payment_methods_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Rendelések
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `address_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('pending','processing','shipped','arrived','canceled','refunded') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','processing','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_status_index` (`status`),
  KEY `orders_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `orders_address_id_foreign` (`address_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Kuponok
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_code` varchar(32) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `usage_limit` int UNSIGNED DEFAULT NULL,
  `used_count` int UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_coupon_code_unique` (`coupon_code`),
  KEY `coupons_coupon_code_index` (`coupon_code`),
  KEY `coupons_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Analitika
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `analytics_sessions`;
CREATE TABLE `analytics_sessions` (
  `id` varchar(64) NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_new_visitor` tinyint(1) NOT NULL DEFAULT '1',
  `started_at` timestamp NOT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_sessions_user_id_index` (`user_id`),
  KEY `analytics_sessions_started_at_index` (`started_at`),
  CONSTRAINT `analytics_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `event_type` enum('pageview','click','time_spent','add_to_cart','remove_from_cart','checkout') NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `element_selector` varchar(255) DEFAULT NULL,
  `duration_seconds` int UNSIGNED DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_session_id_foreign` (`session_id`),
  KEY `events_product_id_foreign` (`product_id`),
  KEY `events_created_at_index` (`created_at`),
  CONSTRAINT `events_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `analytics_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `daily_aggregates`;
CREATE TABLE `daily_aggregates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `hour` tinyint UNSIGNED NOT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `event_type` enum('pageview','click','time_spent','add_to_cart','remove_from_cart','checkout') NOT NULL,
  `event_count` int UNSIGNED NOT NULL DEFAULT '0',
  `unique_sessions` int UNSIGNED NOT NULL DEFAULT '0',
  `avg_duration_sec` float DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `bounce_count` int UNSIGNED NOT NULL DEFAULT '0',
  `new_visitors` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_aggregates_date_index` (`date`),
  KEY `daily_aggregates_hour_index` (`hour`),
  KEY `daily_aggregates_product_id_foreign` (`product_id`),
  CONSTRAINT `daily_aggregates_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Audit log
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `model_type` varchar(64) NOT NULL,
  `model_id` bigint UNSIGNED DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `changes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_model_type_index` (`model_type`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Laravel rendszertáblák (cache, jobs)
-- ──────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────────
-- Mintaadatok
-- ──────────────────────────────────────────────────────────────────

INSERT INTO `users` (`id`,`name`,`email`,`phone`,`email_verified_at`,`password`,`created_at`,`updated_at`) VALUES
(1,'Admin','admin@parfumeria.hu',NULL,NOW(),'$2y$12$8VxvGc/pTjL4vJMSSe7cWuzRmGFEx/8eWJYRzKM5Q6DwJM8vPJ8U6',NOW(),NOW()),
(2,'Kovács Anna','anna.kovacs@example.hu',NULL,NULL,'$2y$12$rYQMXP/8PxqVF7c0Zs4Y9eLbJVOeZx3D7ZwLm4j0LK0nDl/CkuK0K',NOW(),NOW()),
(3,'Nagy Péter','peter.nagy@example.hu',NULL,NULL,'$2y$12$rYQMXP/8PxqVF7c0Zs4Y9eLbJVOeZx3D7ZwLm4j0LK0nDl/CkuK0K',NOW(),NOW()),
(4,'Szabó Bence','bence.szabo@example.hu',NULL,NULL,'$2y$12$rYQMXP/8PxqVF7c0Zs4Y9eLbJVOeZx3D7ZwLm4j0LK0nDl/CkuK0K',NOW(),NOW()),
(5,'Tóth Eszter','eszter.toth@example.hu',NULL,NULL,'$2y$12$rYQMXP/8PxqVF7c0Zs4Y9eLbJVOeZx3D7ZwLm4j0LK0nDl/CkuK0K',NOW(),NOW());

INSERT INTO `admins` (`id`,`name`,`email`,`password`,`role`,`created_at`,`updated_at`) VALUES
(1,'Admin','admin@parfumeria.hu','$2y$12$8VxvGc/pTjL4vJMSSe7cWuzRmGFEx/8eWJYRzKM5Q6DwJM8vPJ8U6','admin',NOW(),NOW());

INSERT INTO `categories` (`id`,`parent_id`,`name`,`slug`,`description`,`is_active`,`created_at`,`updated_at`) VALUES
(1,NULL,'Férfi parfümök','ferfi-parfumok','Exkluzív férfi illatok a világ vezető márkáitól.',1,NOW(),NOW()),
(2,1,'Eau de Parfum','ferfi-eau-de-parfum',NULL,1,NOW(),NOW()),
(3,1,'Eau de Toilette','ferfi-eau-de-toilette',NULL,1,NOW(),NOW()),
(4,1,'Ajándékszett','ferfi-ajandekszett',NULL,1,NOW(),NOW()),
(5,NULL,'Női parfümök','noi-parfumok','Elegáns és romantikus illatok hölgyeknek.',1,NOW(),NOW()),
(6,5,'Eau de Parfum','noi-eau-de-parfum',NULL,1,NOW(),NOW()),
(7,5,'Eau de Toilette','noi-eau-de-toilette',NULL,1,NOW(),NOW()),
(8,5,'Ajándékszett','noi-ajandekszett',NULL,1,NOW(),NOW()),
(9,NULL,'Unisex parfümök','unisex-parfumok','Nemektől független, egyedi illatok.',1,NOW(),NOW()),
(10,9,'Eau de Parfum','unisex-eau-de-parfum',NULL,1,NOW(),NOW()),
(11,9,'Prémium kollekció','unisex-premium',NULL,1,NOW(),NOW());

INSERT INTO `products` (`id`,`category_id`,`name`,`slug`,`description`,`price`,`stock_quantity`,`volume_ml`,`gender`,`is_active`,`created_at`,`updated_at`) VALUES
(1,2,'Bleu de Chanel','bleu-de-chanel','Egy szabad, határokat nem ismerő férfi képét idézi. Friss, tiszta és mélyen fás illatú.',45990.00,25,100,'male',1,NOW(),NOW()),
(2,2,'Sauvage','dior-sauvage','A vadon szelleme. Nyers és nemes egyszerre – bors és ambroxán dominanciával.',42990.00,30,100,'male',1,NOW(),NOW()),
(3,3,'Acqua di Giò','acqua-di-gio','A mediterrán tenger frissessége és a természet ereje. Ikonikus vízi-fás illat.',38990.00,20,100,'male',1,NOW(),NOW()),
(4,3,'Terre d''Hermès','terre-d-hermes','A föld és az ég között. Narancs, szantálfa és vetiver harmonikus ötvözete.',47990.00,15,100,'male',1,NOW(),NOW()),
(5,6,'Chanel No. 5','chanel-no-5','A világ legikonikusabb parfümje. Virágos-aldehid illat, az elegancia szimbóluma.',52990.00,18,100,'female',1,NOW(),NOW()),
(6,6,'La Vie Est Belle','la-vie-est-belle','Az élet szép. Édes és virágos illat, iris és pralina jegyekkel.',39990.00,22,75,'female',1,NOW(),NOW()),
(7,7,'Miss Dior','miss-dior','Friss és virágos illat, amely a modern nőiességet ünnepli. Rózsa és pacsuli.',44990.00,20,100,'female',1,NOW(),NOW()),
(8,7,'Flowerbomb','flowerbomb','Egy virágos robbanás. Édes, intenzív és addiktív illat, jázmin és rózsa szívjegyekkel.',41990.00,17,50,'female',1,NOW(),NOW()),
(9,10,'CK One','ck-one','Az első igazán unisex parfüm. Friss, citrusos és tiszta – mindenki számára.',24990.00,35,100,'unisex',1,NOW(),NOW()),
(10,11,'Oud Wood','tom-ford-oud-wood','Ritka oud fa, szantálfa és brazil rózsa kombinációja. A luxus megtestesítője.',89990.00,8,50,'unisex',1,NOW(),NOW());

INSERT INTO `product_images` (`id`,`product_id`,`image_url`,`sort_order`,`is_primary`,`created_at`,`updated_at`) VALUES
(1,1,'https://placehold.co/600x600?text=Bleu+de+Chanel',1,1,NOW(),NOW()),
(2,2,'https://placehold.co/600x600?text=Sauvage',1,1,NOW(),NOW()),
(3,3,'https://placehold.co/600x600?text=Acqua+di+Gio',1,1,NOW(),NOW()),
(4,4,'https://placehold.co/600x600?text=Terre+d%27Hermes',1,1,NOW(),NOW()),
(5,5,'https://placehold.co/600x600?text=Chanel+No.+5',1,1,NOW(),NOW()),
(6,6,'https://placehold.co/600x600?text=La+Vie+Est+Belle',1,1,NOW(),NOW()),
(7,7,'https://placehold.co/600x600?text=Miss+Dior',1,1,NOW(),NOW()),
(8,8,'https://placehold.co/600x600?text=Flowerbomb',1,1,NOW(),NOW()),
(9,9,'https://placehold.co/600x600?text=CK+One',1,1,NOW(),NOW()),
(10,10,'https://placehold.co/600x600?text=Oud+Wood',1,1,NOW(),NOW());

INSERT INTO `addresses` (`id`,`user_id`,`label`,`country`,`city`,`zip_code`,`street`,`is_default`,`created_at`,`updated_at`) VALUES
(1,2,'Otthon','Magyarország','Budapest','1011','Fő utca 12.',1,NOW(),NOW()),
(2,3,'Otthon','Magyarország','Debrecen','4024','Piac utca 5.',1,NOW(),NOW()),
(3,4,'Otthon','Magyarország','Pécs','7621','Király utca 8.',1,NOW(),NOW()),
(4,5,'Otthon','Magyarország','Győr','9021','Aradi vértanúk útja 3.',1,NOW(),NOW());

INSERT INTO `coupons` (`id`,`coupon_code`,`discount_type`,`discount_value`,`expiry_date`,`usage_limit`,`used_count`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'WELCOME10','percentage',10.00,DATE_ADD(CURDATE(), INTERVAL 3 MONTH),200,12,1,NOW(),NOW()),
(2,'SPRING25','percentage',25.00,DATE_ADD(CURDATE(), INTERVAL 1 MONTH),100,5,1,NOW(),NOW()),
(3,'FIX5000','fixed',5000.00,DATE_ADD(CURDATE(), INTERVAL 2 MONTH),50,0,1,NOW(),NOW()),
(4,'VIP50','percentage',50.00,DATE_ADD(CURDATE(), INTERVAL 2 WEEK),20,18,1,NOW(),NOW()),
(5,'SUMMER15','percentage',15.00,DATE_ADD(CURDATE(), INTERVAL 4 MONTH),NULL,3,1,NOW(),NOW()),
(6,'BLACKFRIDAY','percentage',40.00,DATE_SUB(CURDATE(), INTERVAL 10 DAY),500,487,0,NOW(),NOW()),
(7,'HOLIDAY20','percentage',20.00,DATE_ADD(CURDATE(), INTERVAL 6 MONTH),300,0,1,NOW(),NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- A teljes mintaadatkészletért (10 rendelés tételenként, kívánságlisták,
-- fizetési módok, analitikai munkamenetek) futtasd a Laravel seedereket:
--
--   docker compose exec api php artisan db:seed --force
