USE `hostel_management_system`;

ALTER TABLE `Hostel_Manager`
  MODIFY COLUMN `Hostel_id` int(10) DEFAULT NULL,
  MODIFY COLUMN `Mess_id` int(10) DEFAULT NULL;
