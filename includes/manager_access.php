<?php
function requireManagerAccess($conn, $allowedPositions, $loginLocation, $adminLocation) {
	if (empty($_SESSION['hostel_man_id'])) {
		header('Location: ' . $loginLocation);
		exit();
	}

	$stmt = mysqli_prepare($conn, 'SELECT Hostel_man_id, Hostel_id, Mess_id, Isadmin, Position FROM Hostel_Manager WHERE Hostel_man_id = ? LIMIT 1');
	if (!$stmt) {
		http_response_code(500);
		exit('Could not verify staff access.');
	}
	mysqli_stmt_bind_param($stmt, 'i', $_SESSION['hostel_man_id']);
	if (!mysqli_stmt_execute($stmt)) {
		mysqli_stmt_close($stmt);
		http_response_code(500);
		exit('Could not verify staff access.');
	}
	$manager = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);
	if (!$manager) {
		http_response_code(403);
		exit('Forbidden.');
	}

	$manager['Position'] = $manager['Position'] ?: ((int)$manager['Isadmin'] === 1 ? 'Administrator' : 'Hostel Manager');
	$_SESSION['hostel_id'] = $manager['Hostel_id'];
	$_SESSION['Mess_id'] = $manager['Mess_id'];
	$_SESSION['isadmin'] = $manager['Isadmin'];
	$_SESSION['position'] = $manager['Position'];

	if ((int)$manager['Isadmin'] === 1) {
		header('Location: ' . $adminLocation);
		exit();
	}
	if (!in_array($manager['Position'], $allowedPositions, true)) {
		http_response_code(403);
		exit('Your staff role does not have access to this page.');
	}
	if (in_array($manager['Position'], array('Warden', 'Hostel Manager', 'Guard'), true) && empty($manager['Hostel_id'])) {
		http_response_code(403);
		exit('No hostel is assigned to this staff account.');
	}
	if ($manager['Position'] === 'Mess Manager' && empty($manager['Mess_id'])) {
		http_response_code(403);
		exit('No mess is assigned to this staff account.');
	}
	return $manager;
}
