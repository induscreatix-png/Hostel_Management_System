<?php
session_start();
require 'config.inc.php';

if (empty($_SESSION['hostel_man_id']) || !isset($_SESSION['isadmin']) || (int)$_SESSION['isadmin'] !== 1) {
	header('Location: ../login-hostel_manager.php');
	exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['hm_signup_submit'])) {
	header('Location: ../admin/create_hm.php');
	exit();
}
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
	http_response_code(400);
	exit('Invalid request token.');
}

$username = trim(isset($_POST['hm_uname']) ? $_POST['hm_uname'] : '');
$firstName = trim(isset($_POST['hm_fname']) ? $_POST['hm_fname'] : '');
$lastName = trim(isset($_POST['hm_lname']) ? $_POST['hm_lname'] : '');
$mobile = trim(isset($_POST['hm_mobile']) ? $_POST['hm_mobile'] : '');
$position = isset($_POST['position']) ? $_POST['position'] : '';
$password = isset($_POST['pass']) ? $_POST['pass'] : '';
$confirmPassword = isset($_POST['confpass']) ? $_POST['confpass'] : '';
$hostelId = isset($_POST['hostel_id']) ? filter_var($_POST['hostel_id'], FILTER_VALIDATE_INT) : false;
$messId = isset($_POST['mess_id']) ? filter_var($_POST['mess_id'], FILTER_VALIDATE_INT) : false;
$guardShifts = array('06:00-14:00', '14:00-22:00', '22:00-06:00');
$guardShift = isset($_POST['guard_shift']) ? $_POST['guard_shift'] : '';
$allowedPositions = array('Warden', 'Hostel Manager', 'Guard', 'Mess Manager', 'Finance Reviewer');
$error = '';

if ($position === 'Guard') {
	$columnStmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
	if (!$columnStmt) {
		http_response_code(500);
		exit('Could not check the guard database setup.');
	}
	$tableName = 'Hostel_Manager';
	$columnName = 'Guard_shift';
	mysqli_stmt_bind_param($columnStmt, 'ss', $tableName, $columnName);
	if (!mysqli_stmt_execute($columnStmt)) {
		mysqli_stmt_close($columnStmt);
		http_response_code(500);
		exit('Could not check the guard database setup.');
	}
	$hasGuardShift = mysqli_num_rows(mysqli_stmt_get_result($columnStmt)) > 0;
	mysqli_stmt_close($columnStmt);
	if (!$hasGuardShift) {
		$_SESSION['staff_notice'] = 'Guard setup is not installed. Run database/guard_movement_tracking.sql against hostel_management_system.';
		$_SESSION['staff_notice_type'] = 'error';
		header('Location: ../admin/create_hm.php');
		exit();
	}
}

if (!preg_match('/^[a-zA-Z0-9]+$/', $username) || $firstName === '' || $lastName === '' || $mobile === '') {
	$error = 'Enter valid staff details.';
} elseif (!in_array($position, $allowedPositions, true)) {
	$error = 'Select a valid staff position.';
} elseif (strlen($password) < 8) {
	$error = 'Use a password with at least 8 characters.';
} elseif ($password !== $confirmPassword) {
	$error = 'Entered passwords do not match.';
} elseif (in_array($position, array('Warden', 'Hostel Manager', 'Guard'), true) && (!$hostelId || $hostelId < 1)) {
	$error = 'Select the hostel assigned to this staff member.';
} elseif ($position === 'Guard' && !in_array($guardShift, $guardShifts, true)) {
	$error = 'Select a valid 8-hour guard shift.';
} elseif ($position === 'Mess Manager' && (!$messId || $messId < 1)) {
	$error = 'Select the mess assigned to this staff member.';
} else {
	$hostelId = in_array($position, array('Warden', 'Hostel Manager', 'Guard'), true) ? (int)$hostelId : null;
	$messId = $position === 'Mess Manager' ? (int)$messId : null;
	$assignedAreaExists = true;
	if ($hostelId !== null) {
		$stmt = mysqli_prepare($conn, 'SELECT Hostel_id FROM Hostel WHERE Hostel_id = ? LIMIT 1');
		mysqli_stmt_bind_param($stmt, 'i', $hostelId);
		mysqli_stmt_execute($stmt);
		$assignedAreaExists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
		mysqli_stmt_close($stmt);
	} elseif ($messId !== null) {
		$stmt = mysqli_prepare($conn, 'SELECT Mess_id FROM Mess WHERE Mess_id = ? LIMIT 1');
		mysqli_stmt_bind_param($stmt, 'i', $messId);
		mysqli_stmt_execute($stmt);
		$assignedAreaExists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
		mysqli_stmt_close($stmt);
	}

	if (!$assignedAreaExists) {
		$error = 'The selected hostel or mess does not exist.';
	} else {
		$hash = password_hash($password, PASSWORD_DEFAULT);
		if ($position === 'Guard') {
			$stmt = mysqli_prepare($conn, 'INSERT INTO Hostel_Manager (Username, Fname, Lname, Mob_no, Hostel_id, Mess_id, Pwd, Isadmin, Position, Guard_shift) VALUES (?, ?, ?, ?, ?, NULL, ?, 0, ?, ?)');
			mysqli_stmt_bind_param($stmt, 'ssssisss', $username, $firstName, $lastName, $mobile, $hostelId, $hash, $position, $guardShift);
		} elseif ($hostelId !== null) {
			$stmt = mysqli_prepare($conn, 'INSERT INTO Hostel_Manager (Username, Fname, Lname, Mob_no, Hostel_id, Mess_id, Pwd, Isadmin, Position) VALUES (?, ?, ?, ?, ?, NULL, ?, 0, ?)');
			mysqli_stmt_bind_param($stmt, 'ssssiss', $username, $firstName, $lastName, $mobile, $hostelId, $hash, $position);
		} elseif ($messId !== null) {
			$stmt = mysqli_prepare($conn, 'INSERT INTO Hostel_Manager (Username, Fname, Lname, Mob_no, Hostel_id, Mess_id, Pwd, Isadmin, Position) VALUES (?, ?, ?, ?, NULL, ?, ?, 0, ?)');
			mysqli_stmt_bind_param($stmt, 'ssssiss', $username, $firstName, $lastName, $mobile, $messId, $hash, $position);
		} else {
			$stmt = mysqli_prepare($conn, 'INSERT INTO Hostel_Manager (Username, Fname, Lname, Mob_no, Hostel_id, Mess_id, Pwd, Isadmin, Position) VALUES (?, ?, ?, ?, NULL, NULL, ?, 0, ?)');
			mysqli_stmt_bind_param($stmt, 'ssssss', $username, $firstName, $lastName, $mobile, $hash, $position);
		}
		$created = $stmt && mysqli_stmt_execute($stmt);
		$duplicate = $stmt && mysqli_stmt_errno($stmt) === 1062;
		if ($stmt) {
			mysqli_stmt_close($stmt);
		}
		if (!$created) {
			$error = $duplicate
				? ($position === 'Guard' ? 'That username is already in use, or a guard is already assigned to this shift at the selected hostel.' : 'That username is already in use.')
				: 'The staff account could not be created. Check that the staff-role migration has been applied.';
		}
	}
}

if ($error !== '') {
	$_SESSION['staff_notice'] = $error;
	$_SESSION['staff_notice_type'] = 'error';
} else {
	$_SESSION['staff_notice'] = 'Staff account created successfully with role-specific access.';
	$_SESSION['staff_notice_type'] = 'success';
}
header('Location: ../admin/create_hm.php');
exit();
