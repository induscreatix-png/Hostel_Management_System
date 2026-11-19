USE `hostel_management_system`;

SET @guard_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'Hostel_Manager' AND column_name = 'Guard_shift') = 0,
  'ALTER TABLE `Hostel_Manager` ADD COLUMN `Guard_shift` enum(''06:00-14:00'', ''14:00-22:00'', ''22:00-06:00'') DEFAULT NULL',
  'SELECT 1'
);
PREPARE guard_stmt FROM @guard_ddl;
EXECUTE guard_stmt;
DEALLOCATE PREPARE guard_stmt;

SET @guard_ddl = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'Hostel_Manager' AND index_name = 'Guard_hostel_shift_unique') = 0,
  'CREATE UNIQUE INDEX `Guard_hostel_shift_unique` ON `Hostel_Manager` (`Hostel_id`, `Guard_shift`)',
  'SELECT 1'
);
PREPARE guard_stmt FROM @guard_ddl;
EXECUTE guard_stmt;
DEALLOCATE PREPARE guard_stmt;

CREATE TABLE IF NOT EXISTS `Hostel_Student_Movement` (
  `Movement_id` int(10) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `Hostel_id` int(10) NOT NULL,
  `Guard_id` int(10) DEFAULT NULL,
  `Event_type` enum('Hostel out', 'College entry') NOT NULL,
  `Event_date` date NOT NULL,
  `Recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Movement_id`),
  UNIQUE KEY `Student_daily_event_unique` (`Student_id`, `Event_date`, `Event_type`),
  KEY `Hostel_movement_day_idx` (`Hostel_id`, `Event_date`),
  CONSTRAINT `Hostel_Student_Movement_student_fk` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`),
  CONSTRAINT `Hostel_Student_Movement_hostel_fk` FOREIGN KEY (`Hostel_id`) REFERENCES `Hostel` (`Hostel_id`),
  CONSTRAINT `Hostel_Student_Movement_guard_fk` FOREIGN KEY (`Guard_id`) REFERENCES `Hostel_Manager` (`Hostel_man_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
