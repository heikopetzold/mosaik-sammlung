CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mosaics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `type` VARCHAR(10) NOT NULL DEFAULT 'heft',
  `series` VARCHAR(10) NOT NULL DEFAULT 'abrafaxe',
  `issue_number` INT NULL,
  `availability` VARCHAR(10) NOT NULL DEFAULT 'vorhanden',
  `item_condition` VARCHAR(20) NOT NULL DEFAULT 'sehr_gut',
  `release_year` INT NOT NULL,
  `release_month` INT NOT NULL,
  `description` TEXT NULL,
  `image_path` VARCHAR(255) NULL,
  `image_path_current_condition` VARCHAR(255) NULL,
  INDEX (`release_year`),
  INDEX (`series`),
  INDEX (`item_condition`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
