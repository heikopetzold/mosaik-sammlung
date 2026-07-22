CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mosaics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT 'heft',
  `category` VARCHAR(255) NOT NULL DEFAULT 'Abrafaxe',
  `title` VARCHAR(255) NOT NULL,
  `issue_number` INT NULL,
  `main_serie` VARCHAR(255) NULL,
  `serie` VARCHAR(255) NULL,
  `availability` VARCHAR(255) NOT NULL DEFAULT 'vorhanden',
  `item_condition` VARCHAR(255) NOT NULL DEFAULT 'sehr_gut',
  `release_year` INT NOT NULL,
  `release_month` INT NOT NULL,
  `description` TEXT NULL,
  `image_path` VARCHAR(255) NULL,
  `image_path_current_condition` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_uuid` (`uuid`),
  INDEX `idx_release_year` (`release_year`),
  INDEX `idx_category` (`category`),
  INDEX `idx_main_serie` (`main_serie`),
  INDEX `idx_serie` (`serie`),
  INDEX `idx_item_condition` (`item_condition`),
  INDEX `idx_availability` (`availability`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Standard-Admin anlegen (Passwort: admin123)
INSERT INTO `users` (`username`, `password`)
VALUES ('admin', '$2y$12$t1yByWGLhG2POsadvBlXwui6kdHBD5Y0yy7w03PtK38w2XecckjzK')
ON DUPLICATE KEY UPDATE id=id;
