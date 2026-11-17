USE `hostel_management_system`;

CREATE TABLE IF NOT EXISTS `Mess` (
  `Mess_id` int(10) NOT NULL AUTO_INCREMENT,
  `Mess_name` varchar(255) NOT NULL,
  `Mess_type` varchar(255) NOT NULL,
  `Vacancy` int(15) DEFAULT 5,
  `Size` int(15) DEFAULT 5,
  PRIMARY KEY (`Mess_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `Mess_Allocation` (
  `Mess_card_id` int(10) NOT NULL AUTO_INCREMENT,
  `Mess_id` int(10) NOT NULL,
  `Mess_card_No` int(10) NOT NULL,
  `Allocated` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`Mess_card_id`),
  KEY `Mess_allocation_mess_idx` (`Mess_id`),
  CONSTRAINT `Feature_mess_allocation_mess_fk` FOREIGN KEY (`Mess_id`) REFERENCES `Mess` (`Mess_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `Application_mess` (
  `Application_id` int(100) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `Mess_id` int(10) NOT NULL,
  `Application_status` tinyint(1) DEFAULT 1,
  `Mess_card_No` int(10) DEFAULT NULL,
  `Message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Application_id`),
  KEY `Application_mess_student_idx` (`Student_id`),
  KEY `Application_mess_mess_idx` (`Mess_id`),
  CONSTRAINT `Feature_application_mess_student_fk` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`),
  CONSTRAINT `Feature_application_mess_mess_fk` FOREIGN KEY (`Mess_id`) REFERENCES `Mess` (`Mess_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `Payment` (
  `Student_id` varchar(255) NOT NULL,
  `Status` tinyint(1) DEFAULT 0,
  `Amount` int(10) DEFAULT 75000,
  PRIMARY KEY (`Student_id`),
  CONSTRAINT `Feature_payment_student_fk` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `Complaint` (
  `Complaint_id` int(10) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `Hostel_id` int(10) DEFAULT NULL,
  `Subject` varchar(150) NOT NULL,
  `Details` text NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Pending',
  `Response` text DEFAULT NULL,
  `Created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`Complaint_id`),
  KEY `Complaint_student_status_idx` (`Student_id`, `Status`),
  KEY `Complaint_hostel_status_idx` (`Hostel_id`, `Status`),
  CONSTRAINT `Feature_complaint_student_fk` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`),
  CONSTRAINT `Feature_complaint_hostel_fk` FOREIGN KEY (`Hostel_id`) REFERENCES `Hostel` (`Hostel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET @feature_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Student' AND column_name = 'Mess_id') = 0,
  'ALTER TABLE `Student` ADD COLUMN `Mess_id` int(10) DEFAULT NULL',
  'SELECT 1'
);
PREPARE feature_stmt FROM @feature_ddl;
EXECUTE feature_stmt;
DEALLOCATE PREPARE feature_stmt;

SET @feature_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Student' AND column_name = 'Mess_card_id') = 0,
  'ALTER TABLE `Student` ADD COLUMN `Mess_card_id` int(10) DEFAULT NULL',
  'SELECT 1'
);
PREPARE feature_stmt FROM @feature_ddl;
EXECUTE feature_stmt;
DEALLOCATE PREPARE feature_stmt;

SET @feature_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Hostel_Manager' AND column_name = 'Mess_id') = 0,
  'ALTER TABLE `Hostel_Manager` ADD COLUMN `Mess_id` int(10) DEFAULT NULL',
  'SELECT 1'
);
PREPARE feature_stmt FROM @feature_ddl;
EXECUTE feature_stmt;
DEALLOCATE PREPARE feature_stmt;

INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'A', 'Veg', 5, 5 WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'A');
INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'B', 'Veg', 5, 5 WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'B');
INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'C', 'Veg', 5, 5 WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'C');
INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'D', 'Non-veg', 5, 5 WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'D');
INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'E', 'Non-veg', 5, 5 WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'E');
INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'F', 'Non-veg', 5, 5 WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'F');

INSERT INTO `Mess_Allocation` (`Mess_id`, `Mess_card_No`, `Allocated`)
SELECT m.`Mess_id`, cards.`card_no`, 0
FROM `Mess` m
JOIN (
  SELECT 1 AS `card_no` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) cards
WHERE NOT EXISTS (
  SELECT 1 FROM `Mess_Allocation` ma
  WHERE ma.`Mess_id` = m.`Mess_id` AND ma.`Mess_card_No` = cards.`card_no`
);

INSERT INTO `Payment` (`Student_id`, `Status`, `Amount`)
SELECT s.`Student_id`, 0, 75000
FROM `Student` s
WHERE NOT EXISTS (SELECT 1 FROM `Payment` p WHERE p.`Student_id` = s.`Student_id`);

UPDATE `Hostel_Manager` hm
JOIN `Hostel` h ON h.`Hostel_id` = hm.`Hostel_id`
JOIN `Mess` m ON m.`Mess_name` = REPLACE(h.`Hostel_name`, ' Hostel', '')
SET hm.`Mess_id` = m.`Mess_id`
WHERE hm.`Mess_id` IS NULL;

UPDATE `Mess` m
SET m.`Vacancy` = GREATEST(0, m.`Size` - (
  SELECT COUNT(*) FROM `Mess_Allocation` ma
  WHERE ma.`Mess_id` = m.`Mess_id` AND ma.`Allocated` = 1
));
