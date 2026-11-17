USE `hostel_management_system`;

ALTER TABLE `Room`
  ADD COLUMN IF NOT EXISTS `Capacity` tinyint unsigned NOT NULL DEFAULT 5,
  MODIFY COLUMN `Allocated` tinyint unsigned NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `Room_Capacity_Expansion_Log` (
  `Hostel_id` int(10) NOT NULL,
  `First_new_room_no` int(10) NOT NULL,
  `Rooms_to_add` tinyint unsigned NOT NULL DEFAULT 6,
  PRIMARY KEY (`Hostel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT IGNORE INTO `Room_Capacity_Expansion_Log` (`Hostel_id`, `First_new_room_no`, `Rooms_to_add`)
SELECT h.`Hostel_id`, COALESCE(MAX(r.`Room_No`), 0) + 1, 6
FROM `Hostel` h
LEFT JOIN `Room` r ON r.`Hostel_id` = h.`Hostel_id`
GROUP BY h.`Hostel_id`;

DROP TRIGGER IF EXISTS `room_capacity_guard`;
DROP TRIGGER IF EXISTS `allocation_update`;

UPDATE `Room` r
SET r.`Allocated` = (
  SELECT COUNT(*)
  FROM `Student` s
  WHERE s.`Room_id` = r.`Room_id`
);

INSERT INTO `Room` (`Hostel_id`, `Room_No`, `Allocated`, `Capacity`)
SELECT h.`Hostel_id`, expansion.`First_new_room_no` + room_offsets.`Room_Offset` - 1, 0, 5
FROM `Hostel` h
JOIN `Room_Capacity_Expansion_Log` expansion
  ON expansion.`Hostel_id` = h.`Hostel_id`
CROSS JOIN (
  SELECT 1 AS `Room_Offset` UNION ALL SELECT 2 UNION ALL SELECT 3
  UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
) room_offsets
WHERE room_offsets.`Room_Offset` <= expansion.`Rooms_to_add`
  AND NOT EXISTS (
  SELECT 1
  FROM `Room` existing
  WHERE existing.`Hostel_id` = h.`Hostel_id`
    AND existing.`Room_No` = expansion.`First_new_room_no` + room_offsets.`Room_Offset` - 1
);

UPDATE `Hostel` h
SET h.`No_of_rooms` = (
  SELECT COUNT(*) FROM `Room` r WHERE r.`Hostel_id` = h.`Hostel_id`
),
h.`current_no_of_rooms` = (
  SELECT COUNT(*) FROM `Room` r
  WHERE r.`Hostel_id` = h.`Hostel_id` AND r.`Allocated` > 0
),
h.`No_of_students` = (
  SELECT COUNT(*) FROM `Student` s
  WHERE s.`Hostel_id` = h.`Hostel_id` AND s.`Room_id` IS NOT NULL
);

DELIMITER //
CREATE TRIGGER `room_capacity_guard` BEFORE UPDATE ON `Room`
FOR EACH ROW
BEGIN
  IF NEW.`Allocated` > NEW.`Capacity` THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Room capacity exceeded';
  END IF;
END//

CREATE TRIGGER `allocation_update` AFTER UPDATE ON `Room`
FOR EACH ROW
BEGIN
  IF NEW.`Allocated` <> OLD.`Allocated` THEN
    UPDATE `Hostel`
    SET `current_no_of_rooms` = `current_no_of_rooms` +
      CASE
        WHEN OLD.`Allocated` = 0 AND NEW.`Allocated` > 0 THEN 1
        WHEN OLD.`Allocated` > 0 AND NEW.`Allocated` = 0 THEN -1
        ELSE 0
      END,
      `No_of_students` = `No_of_students` + NEW.`Allocated` - OLD.`Allocated`
    WHERE `Hostel_id` = NEW.`Hostel_id`;
  END IF;
END//
DELIMITER ;
