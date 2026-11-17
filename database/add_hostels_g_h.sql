USE `hostel_management_system`;

INSERT INTO `Hostel` (`Hostel_name`, `current_no_of_rooms`, `No_of_rooms`, `No_of_students`)
SELECT 'G', 0, 5, 0
WHERE NOT EXISTS (SELECT 1 FROM `Hostel` WHERE `Hostel_name` = 'G');

INSERT INTO `Hostel` (`Hostel_name`, `current_no_of_rooms`, `No_of_rooms`, `No_of_students`)
SELECT 'H', 0, 5, 0
WHERE NOT EXISTS (SELECT 1 FROM `Hostel` WHERE `Hostel_name` = 'H');

UPDATE `Hostel`
SET `No_of_rooms` = 5
WHERE `Hostel_name` IN ('G', 'H');

INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'G', 'Mixed', 5, 5
WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'G');

INSERT INTO `Mess` (`Mess_name`, `Mess_type`, `Vacancy`, `Size`)
SELECT 'H', 'Mixed', 5, 5
WHERE NOT EXISTS (SELECT 1 FROM `Mess` WHERE `Mess_name` = 'H');

UPDATE `Mess`
SET `Mess_type` = 'Mixed'
WHERE `Mess_name` IN ('G', 'H');

INSERT INTO `Room` (`Hostel_id`, `Room_No`, `Allocated`)
SELECT h.`Hostel_id`, rooms.`Room_No`, 0
FROM `Hostel` h
JOIN (
  SELECT 1 AS `Room_No` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) rooms
WHERE h.`Hostel_name` IN ('G', 'H')
  AND NOT EXISTS (
    SELECT 1 FROM `Room` r
    WHERE r.`Hostel_id` = h.`Hostel_id` AND r.`Room_No` = rooms.`Room_No`
  );

INSERT INTO `Mess_Allocation` (`Mess_id`, `Mess_card_No`, `Allocated`)
SELECT m.`Mess_id`, cards.`Mess_card_No`, 0
FROM `Mess` m
JOIN (
  SELECT 1 AS `Mess_card_No` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) cards
WHERE m.`Mess_name` IN ('G', 'H')
  AND NOT EXISTS (
    SELECT 1 FROM `Mess_Allocation` ma
    WHERE ma.`Mess_id` = m.`Mess_id` AND ma.`Mess_card_No` = cards.`Mess_card_No`
  );
