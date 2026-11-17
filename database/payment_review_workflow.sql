USE `hostel_management_system`;

SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Hostel_id') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Hostel_id` int(10) DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;
SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Mess_id') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Mess_id` int(10) DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;
SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Transaction_id') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Transaction_id` varchar(120) DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;
SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Proof_path') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Proof_path` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;
SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Submitted_at') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Submitted_at` timestamp NULL DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;
SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Reviewed_at') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Reviewed_at` timestamp NULL DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;
SET @payment_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Payment' AND column_name = 'Review_note') = 0, 'ALTER TABLE `Payment` ADD COLUMN `Review_note` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE payment_stmt FROM @payment_ddl; EXECUTE payment_stmt; DEALLOCATE PREPARE payment_stmt;

SET @payment_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Hostel_Manager' AND column_name = 'Position') = 0,
  'ALTER TABLE `Hostel_Manager` ADD COLUMN `Position` varchar(60) DEFAULT NULL',
  'SELECT 1'
);
PREPARE payment_stmt FROM @payment_ddl;
EXECUTE payment_stmt;
DEALLOCATE PREPARE payment_stmt;

UPDATE `Hostel_Manager`
SET `Position` = IF(`Isadmin` = 1, 'Administrator', 'Hostel Manager')
WHERE `Position` IS NULL;

CREATE TABLE IF NOT EXISTS `Service_Fee` (
  `Service_type` enum('hostel', 'mess') NOT NULL,
  `Service_id` int(10) NOT NULL,
  `Amount` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`Service_type`, `Service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT IGNORE INTO `Service_Fee` (`Service_type`, `Service_id`, `Amount`)
SELECT 'hostel', `Hostel_id`,
  CASE `Hostel_name` WHEN 'A' THEN 27000 WHEN 'G' THEN 13000 ELSE NULL END
FROM `Hostel`;

INSERT IGNORE INTO `Service_Fee` (`Service_type`, `Service_id`, `Amount`)
SELECT 'mess', `Mess_id`,
  CASE `Mess_name` WHEN 'A' THEN 45000 WHEN 'G' THEN 25000 ELSE NULL END
FROM `Mess`;

SET @payment_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'Payment' AND index_name = 'Payment_status_idx') = 0,
  'CREATE INDEX `Payment_status_idx` ON `Payment` (`Status`)',
  'SELECT 1'
);
PREPARE payment_stmt FROM @payment_ddl;
EXECUTE payment_stmt;
DEALLOCATE PREPARE payment_stmt;
