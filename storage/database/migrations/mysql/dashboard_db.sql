CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `kategori` enum('pupuk','benih','pestisida','alat') NOT NULL,
  `stok` int(11) DEFAULT 0,
  `harga` decimal(15,2) DEFAULT 0.00,
  `status_kritis` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_stok` (`stok`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `default_icon` varchar(50) DEFAULT 'fa-circle',
  `default_color` varchar(30) DEFAULT 'slate',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `member` varchar(255) NOT NULL,
  `time` time NOT NULL,
  `status` enum('Selesai','Proses') NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_activity_category` (`category_id`),
  CONSTRAINT `fk_activity_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` varchar(20) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('ready','working','maintenance') DEFAULT 'ready',
  `health` tinyint(3) unsigned DEFAULT NULL CHECK (`health` <= 100),
  `icon` varchar(50) DEFAULT 'fa-box',
  `color` varchar(30) DEFAULT 'slate',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `asset_maintenance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `maintenance_date` date NOT NULL,
  `task` varchar(255) NOT NULL,
  `status` enum('Selesai','Pending','Proses') DEFAULT 'Selesai',
  `technician_name` varchar(100) DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_logs_asset_id` (`asset_id`),
  CONSTRAINT `fk_logs_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;