<?php
session_start();
require 'config.inc.php';

if (empty($_SESSION['hostel_man_id']) || !isset($_SESSION['isadmin']) || (int)$_SESSION['isadmin'] !== 1) {
	http_response_code(403);
	exit('Forbidden.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['hm_remove_submit'])) {
	header('Location: ../admin/create_hm.php');
	exit();
}
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
	http_response_code(400);
	exit('Invalid request token.');
}

$managerId = filter_input(INPUT_POST, 'manager_id', FILTER_VALIDATE_INT);
if (!$managerId || $managerId < 1) {
	$_SESSION['staff_notice'] = 'Select a valid staff account.';
	$_SESSION['staff_notice_type'] = 'error';
} else {
	$stmt = mysqli_prepare($conn, 'DELETE FROM Hostel_Manager WHERE Hostel_man_id = ? AND Isadmin = 0');
	if (!$stmt) {
		http_response_code(500);
		exit('Could not remove the staff account.');
	}
	mysqli_stmt_bind_param($stmt, 'i', $managerId);
	$removed = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
	mysqli_stmt_close($stmt);
	$_SESSION['staff_notice'] = $removed ? 'Staff account removed.' : 'The staff account could not be found or removed.';
	$_SESSION['staff_notice_type'] = $removed ? 'success' : 'error';
}
header('Location: ../admin/create_hm.php');
exit();
