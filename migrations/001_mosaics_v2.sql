-- Idempotent migration from the initial schema (series column etc.) to the new schema
-- required by README.md.
--
-- Run (Lando default DB):
--   lando mysql lamp < migrations/001_mosaics_v2.sql

SET @db := DATABASE();

-- 1) Rename series -> category if needed
SELECT COUNT(*) INTO @has_series
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'series';

SELECT COUNT(*) INTO @has_category
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'category';

SET @sql := NULL;
SET @sql := IF(
  @has_series = 1 AND @has_category = 0,
  "ALTER TABLE `mosaics` CHANGE COLUMN `series` `category` VARCHAR(255) NOT NULL DEFAULT 'Abrafaxe'",
  "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Ensure wider varchar columns (safe to run repeatedly)
ALTER TABLE `mosaics`
  MODIFY COLUMN `type` VARCHAR(255) NOT NULL DEFAULT 'heft',
  MODIFY COLUMN `availability` VARCHAR(255) NOT NULL DEFAULT 'vorhanden',
  MODIFY COLUMN `item_condition` VARCHAR(255) NOT NULL DEFAULT 'sehr_gut',
  MODIFY COLUMN `category` VARCHAR(255) NOT NULL DEFAULT 'Abrafaxe';

-- 3) Add missing columns (conditionally)
SELECT COUNT(*) INTO @has_uuid
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'uuid';
SET @sql := IF(@has_uuid = 0, "ALTER TABLE `mosaics` ADD COLUMN `uuid` VARCHAR(255) NULL AFTER `id`", NULL);
SET @sql := IF(@has_uuid = 0, "ALTER TABLE `mosaics` ADD COLUMN `uuid` VARCHAR(255) NULL AFTER `id`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_main_serie
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'main_serie';
SET @sql := IF(@has_main_serie = 0, "ALTER TABLE `mosaics` ADD COLUMN `main_serie` VARCHAR(255) NULL AFTER `issue_number`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_serie
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'serie';
SET @sql := IF(@has_serie = 0, "ALTER TABLE `mosaics` ADD COLUMN `serie` VARCHAR(255) NULL AFTER `main_serie`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_created_at
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'created_at';
SET @sql := IF(@has_created_at = 0, "ALTER TABLE `mosaics` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `image_path_current_condition`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_updated_at
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND COLUMN_NAME = 'updated_at';
SET @sql := IF(@has_updated_at = 0, "ALTER TABLE `mosaics` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Backfill uuid for existing rows and enforce NOT NULL + UNIQUE
UPDATE `mosaics` SET `uuid` = UUID() WHERE `uuid` IS NULL OR `uuid` = '';
ALTER TABLE `mosaics` MODIFY COLUMN `uuid` VARCHAR(255) NOT NULL;

SELECT COUNT(*) INTO @has_uniq_uuid
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'uniq_uuid';
SET @sql := IF(@has_uniq_uuid = 0, "ALTER TABLE `mosaics` ADD UNIQUE KEY `uniq_uuid` (`uuid`)", NULL);
SET @sql := IF(@has_uniq_uuid = 0, "ALTER TABLE `mosaics` ADD UNIQUE KEY `uniq_uuid` (`uuid`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Index cleanup + add required indexes
SELECT COUNT(*) INTO @has_idx_series
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'idx_series';
SET @sql := IF(@has_idx_series = 1, "DROP INDEX `idx_series` ON `mosaics`", NULL);
SET @sql := IF(@has_idx_series = 1, "DROP INDEX `idx_series` ON `mosaics`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_series_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'series';
SET @sql := IF(@has_series_idx = 1, "DROP INDEX `series` ON `mosaics`", NULL);
SET @sql := IF(@has_series_idx = 1, "DROP INDEX `series` ON `mosaics`", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_idx_category
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'idx_category';
SET @sql := IF(@has_idx_category = 0, "CREATE INDEX `idx_category` ON `mosaics` (`category`)", NULL);
SET @sql := IF(@has_idx_category = 0, "CREATE INDEX `idx_category` ON `mosaics` (`category`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_idx_main_serie
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'idx_main_serie';
SET @sql := IF(@has_idx_main_serie = 0, "CREATE INDEX `idx_main_serie` ON `mosaics` (`main_serie`)", NULL);
SET @sql := IF(@has_idx_main_serie = 0, "CREATE INDEX `idx_main_serie` ON `mosaics` (`main_serie`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_idx_serie
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'idx_serie';
SET @sql := IF(@has_idx_serie = 0, "CREATE INDEX `idx_serie` ON `mosaics` (`serie`)", NULL);
SET @sql := IF(@has_idx_serie = 0, "CREATE INDEX `idx_serie` ON `mosaics` (`serie`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_idx_availability
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'mosaics' AND INDEX_NAME = 'idx_availability';
SET @sql := IF(@has_idx_availability = 0, "CREATE INDEX `idx_availability` ON `mosaics` (`availability`)", NULL);
SET @sql := IF(@has_idx_availability = 0, "CREATE INDEX `idx_availability` ON `mosaics` (`availability`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure the default admin exists (login: admin / admin123)
INSERT INTO `users` (`username`, `password`)
VALUES ('admin', '$2y$12$t1yByWGLhG2POsadvBlXwui6kdHBD5Y0yy7w03PtK38w2XecckjzK')
ON DUPLICATE KEY UPDATE id=id;
