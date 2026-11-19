<?php
session_start();
require 'config.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login-submit'])) {
	header('Location: ../login-hostel_manager.php');
	exit();
}
$username = trim(isset($_POST['username']) ? $_POST['username'] : '');
$password = isset($_POST['pwd']) ? $_POST['pwd'] : '';
if ($username === '' || $password === '') {
	header('Location: ../login-hostel_manager.php');
	exit();
}

$positionCheck = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
$tableName = 'Hostel_Manager';
$columnName = 'Position';
mysqli_stmt_bind_param($positionCheck, 'ss', $tableName, $columnName);
mysqli_stmt_execute($positionCheck);
$hasPosition = mysqli_num_rows(mysqli_stmt_get_result($positionCheck)) > 0;
mysqli_stmt_close($positionCheck);
$positionField = $hasPosition ? 'Position' : 'NULL AS Position';
$stmt = mysqli_prepare($conn, 'SELECT Hostel_man_id, Username, Fname, Lname, Mob_no, Hostel_id, Mess_id, Pwd, Isadmin, ' . $positionField . ' FROM Hostel_Manager WHERE Username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$manager = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$passwordValid = $manager && (password_verify($password, $manager['Pwd']) || hash_equals((string)$manager['Pwd'], $password));
if (!$passwordValid) {
	header('Location: ../login-hostel_manager.php');
	exit();
}

if (hash_equals((string)$manager['Pwd'], $password)) {
	$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
	$update = mysqli_prepare($conn, 'UPDATE Hostel_Manager SET Pwd = ? WHERE Hostel_man_id = ?');
	mysqli_stmt_bind_param($update, 'si', $hashedPassword, $manager['Hostel_man_id']);
	mysqli_stmt_execute($update);
	mysqli_stmt_close($update);
}

$_SESSION['hostel_man_id'] = $manager['Hostel_man_id'];
$_SESSION['fname'] = $manager['Fname'];
$_SESSION['lname'] = $manager['Lname'];
$_SESSION['mob_no'] = $manager['Mob_no'];
$_SESSION['username'] = $manager['Username'];
$_SESSION['hostel_id'] = $manager['Hostel_id'];
$_SESSION['isadmin'] = $manager['Isadmin'];
$_SESSION['PSWD'] = $manager['Pwd'];
$_SESSION['Mess_id'] = $manager['Mess_id'];
$_SESSION['position'] = !empty($manager['Position']) ? $manager['Position'] : ((int)$manager['Isadmin'] === 1 ? 'Administrator' : 'Hostel Manager');

if ((int)$manager['Isadmin'] === 1) {
	header('Location: ../admin/admin_home.php');
} elseif ($_SESSION['position'] === 'Finance Reviewer') {
	header('Location: ../admin/payments.php');
} elseif ($_SESSION['position'] === 'Guard') {
	header('Location: ../guard_movement.php');
} else {
	header('Location: ../home_manager.php');
}
exit();
