USE `hostel_management_system`;

CREATE TABLE IF NOT EXISTS `Room_Change_Application` (
  `Application_id` int(10) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `Current_room_id` int(10) NOT NULL,
  `Requested_hostel_id` int(10) NOT NULL,
  `Status` tinyint NOT NULL DEFAULT 1,
  `Message` varchar(255) DEFAULT NULL,
  `Created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`Application_id`),
  KEY `Room_change_student_status` (`Student_id`, `Status`),
  KEY `Room_change_hostel_status` (`Requested_hostel_id`, `Status`),
  CONSTRAINT `Room_change_student_fk` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`),
  CONSTRAINT `Room_change_current_room_fk` FOREIGN KEY (`Current_room_id`) REFERENCES `Room` (`Room_id`),
  CONSTRAINT `Room_change_hostel_fk` FOREIGN KEY (`Requested_hostel_id`) REFERENCES `Hostel` (`Hostel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `Passout_Application` (
  `Application_id` int(10) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `Status` tinyint NOT NULL DEFAULT 1,
  `Message` varchar(255) DEFAULT NULL,
  `Created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`Application_id`),
  KEY `Passout_student_status` (`Student_id`, `Status`),
  CONSTRAINT `Passout_student_fk` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
