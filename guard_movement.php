<?php
session_start();
require 'includes/config.inc.php';
require 'includes/manager_access.php';

$guard = requireManagerAccess($conn, array('Guard'), 'login-hostel_manager.php', 'admin/admin_home.php');
$hostelId = (int)$guard['Hostel_id'];
$guardId = (int)$guard['Hostel_man_id'];
$events = array(
	'Hostel out' => 'Hostel out',
	'College entry' => 'College entry'
);

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}

	$studentId = trim(isset($_POST['student_id']) ? $_POST['student_id'] : '');
	$eventType = isset($_POST['event_type']) ? $_POST['event_type'] : '';
	if ($studentId === '' || !isset($events[$eventType])) {
		$_SESSION['movement_notice'] = 'Select a valid student and movement event.';
		$_SESSION['movement_notice_type'] = 'error';
	} else {
		$stmt = mysqli_prepare($conn, 'INSERT INTO Hostel_Student_Movement (Student_id, Hostel_id, Guard_id, Event_type, Event_date, Recorded_at) SELECT s.Student_id, s.Hostel_id, ?, ?, CURRENT_DATE(), CURRENT_TIMESTAMP FROM Student s WHERE s.Student_id = ? AND s.Hostel_id = ?');
		if (!$stmt) {
			http_response_code(500);
			exit('Could not prepare the student movement record. Apply the guard movement database migration and try again.');
		}
		mysqli_stmt_bind_param($stmt, 'issi', $guardId, $eventType, $studentId, $hostelId);
		$recorded = mysqli_stmt_execute($stmt);
		$errorCode = mysqli_stmt_errno($stmt);
		$affectedRows = mysqli_stmt_affected_rows($stmt);
		mysqli_stmt_close($stmt);

		if (!$recorded && $errorCode === 1062) {
			$_SESSION['movement_notice'] = 'This movement event has already been recorded for the student today.';
			$_SESSION['movement_notice_type'] = 'error';
		} elseif (!$recorded) {
			http_response_code(500);
			exit('The student movement could not be recorded.');
		} elseif ($affectedRows !== 1) {
			$_SESSION['movement_notice'] = 'That student is not currently assigned to your hostel.';
			$_SESSION['movement_notice_type'] = 'error';
		} else {
			$_SESSION['movement_notice'] = $eventType . ' time recorded.';
			$_SESSION['movement_notice_type'] = 'success';
		}
	}

	header('Location: guard_movement.php');
	exit();
}

$stmt = mysqli_prepare($conn, 'SELECT Guard_shift FROM Hostel_Manager WHERE Hostel_man_id = ? AND Position = \'Guard\' LIMIT 1');
if (!$stmt) {
	http_response_code(500);
	exit('Could not load the guard shift.');
}
mysqli_stmt_bind_param($stmt, 'i', $guardId);
if (!mysqli_stmt_execute($stmt)) {
	mysqli_stmt_close($stmt);
	http_response_code(500);
	exit('Could not load the guard shift.');
}
$guardAccount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$guardAccount) {
	http_response_code(403);
	exit('Guard account not found.');
}

$studentStmt = mysqli_prepare($conn, 'SELECT s.Student_id, s.Fname, s.Lname, s.Dept, s.Year_of_study, r.Room_No, (SELECT m.Recorded_at FROM Hostel_Student_Movement m WHERE m.Student_id = s.Student_id AND m.Hostel_id = s.Hostel_id AND m.Event_date = CURRENT_DATE() AND m.Event_type = \'Hostel out\' LIMIT 1) AS Hostel_out_at, (SELECT m.Recorded_at FROM Hostel_Student_Movement m WHERE m.Student_id = s.Student_id AND m.Hostel_id = s.Hostel_id AND m.Event_date = CURRENT_DATE() AND m.Event_type = \'College entry\' LIMIT 1) AS College_entry_at FROM Student s LEFT JOIN Room r ON r.Room_id = s.Room_id WHERE s.Hostel_id = ? ORDER BY s.Fname, s.Lname, s.Student_id');
if (!$studentStmt) {
	http_response_code(500);
	exit('Could not load the students for this hostel. Apply the guard movement database migration and try again.');
}
mysqli_stmt_bind_param($studentStmt, 'i', $hostelId);
if (!mysqli_stmt_execute($studentStmt)) {
	mysqli_stmt_close($studentStmt);
	http_response_code(500);
	exit('Could not load the students for this hostel.');
}
$students = mysqli_stmt_get_result($studentStmt);
$notice = isset($_SESSION['movement_notice']) ? $_SESSION['movement_notice'] : '';
$noticeType = isset($_SESSION['movement_notice_type']) ? $_SESSION['movement_notice_type'] : 'success';
unset($_SESSION['movement_notice'], $_SESSION['movement_notice_type']);

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Student movement | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web/css/student-dashboard.css">
	<link rel="stylesheet" href="web/css/admin-dashboard.css">
	<link rel="stylesheet" href="web/css/staff-dashboard.css?v=1">
</head>
<body class="student-dashboard staff-dashboard staff-movement-page">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero">
		<div><p class="eyebrow">GUARD · <?php echo h($guardAccount['Guard_shift'] ?: 'Shift not assigned'); ?></p><h1>Student movement</h1><p>Record hostel departures and college arrivals. Times are recorded automatically for your assigned hostel.</p></div>
	</section>
	<?php if ($notice !== ''): ?><div class="notice <?php echo $noticeType === 'error' ? 'error' : 'success'; ?>" role="status"><?php echo h($notice); ?></div><?php endif; ?>
	<section class="panel directory-panel">
		<div class="panel-heading"><div><p class="eyebrow">TODAY'S ROSTER</p><h2>Students in your assigned hostel</h2></div></div>
		<p class="panel-description">Each event can be recorded once per student per day. Use College entry when the student arrives at college.</p>
		<div class="table-responsive">
			<table class="table admin-table staff-movement-table">
				<thead><tr><th>Student</th><th>Student ID</th><th>Room</th><th>Department / year</th><th>Hostel out</th><th>College entry</th><th>Record event</th></tr></thead>
				<tbody>
					<?php if (mysqli_num_rows($students) === 0): ?><tr><td colspan="7" class="empty-state">No students are currently assigned to this hostel.</td></tr><?php endif; ?>
					<?php while ($student = mysqli_fetch_assoc($students)): ?>
					<tr>
						<td><?php echo h($student['Fname'] . ' ' . $student['Lname']); ?></td>
						<td><?php echo h($student['Student_id']); ?></td>
						<td><?php echo h($student['Room_No'] ?: '-'); ?></td>
						<td><?php echo h($student['Dept'] . ' · Year ' . $student['Year_of_study']); ?></td>
						<td><?php echo $student['Hostel_out_at'] ? h($student['Hostel_out_at']) : 'Not recorded'; ?></td>
						<td><?php echo $student['College_entry_at'] ? h($student['College_entry_at']) : 'Not recorded'; ?></td>
						<td class="movement-actions">
							<?php foreach ($events as $eventValue => $eventLabel):
								$alreadyRecorded = $eventValue === 'Hostel out' ? !empty($student['Hostel_out_at']) : !empty($student['College_entry_at']);
							?>
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
								<input type="hidden" name="student_id" value="<?php echo h($student['Student_id']); ?>">
								<input type="hidden" name="event_type" value="<?php echo h($eventValue); ?>">
								<button class="button button-outline" type="submit" <?php echo $alreadyRecorded ? 'disabled' : ''; ?>><?php echo h($eventLabel); ?></button>
							</form>
							<?php endforeach; ?>
						</td>
					</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>
<?php mysqli_stmt_close($studentStmt); require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
