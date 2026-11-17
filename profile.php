<?php
session_start();
require 'includes/config.inc.php';

function dbTableExists($conn, $table) {
	$stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
	mysqli_stmt_bind_param($stmt, 's', $table);
	mysqli_stmt_execute($stmt);
	$exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
	mysqli_stmt_close($stmt);
	return $exists;
}

function dbColumnExists($conn, $table, $column) {
	$stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
	mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
	mysqli_stmt_execute($stmt);
	$exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
	mysqli_stmt_close($stmt);
	return $exists;
}

if (empty($_SESSION['roll'])) {
	header('Location: index.php');
	exit();
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$studentId = $_SESSION['roll'];
$notice = isset($_SESSION['profile_notice']) ? $_SESSION['profile_notice'] : '';
unset($_SESSION['profile_notice']);
$noticeType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';
	if ($action === 'submit_passout' || $action === 'submit_room_change') {
		$requestTablesAvailable = dbTableExists($conn, 'Passout_Application') && dbTableExists($conn, 'Room_Change_Application');
		if (!$requestTablesAvailable) {
			$notice = 'Leave and room-change requests are not configured. Run database/student_application_workflow.sql.';
			$noticeType = 'error';
		} else {
			$stmt = mysqli_prepare($conn, 'SELECT Hostel_id, Room_id FROM Student WHERE Student_id = ?');
			mysqli_stmt_bind_param($stmt, 's', $studentId);
			mysqli_stmt_execute($stmt);
			$allocation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
			mysqli_stmt_close($stmt);
			if (!$allocation || empty($allocation['Hostel_id']) || empty($allocation['Room_id'])) {
				$notice = 'You need an active hostel room allocation before submitting this request.';
				$noticeType = 'error';
			} elseif ($action === 'submit_passout') {
				$stmt = mysqli_prepare($conn, 'SELECT Application_id FROM Passout_Application WHERE Student_id = ? AND Status = 1 LIMIT 1');
				mysqli_stmt_bind_param($stmt, 's', $studentId);
				mysqli_stmt_execute($stmt);
				$pending = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
				mysqli_stmt_close($stmt);
				if ($pending) {
					$notice = 'You already have a pending leave-hostel request.';
					$noticeType = 'error';
				} else {
					$message = trim(isset($_POST['message']) ? $_POST['message'] : '');
					$stmt = mysqli_prepare($conn, 'INSERT INTO Passout_Application (Student_id, Status, Message) VALUES (?, 1, ?)');
					mysqli_stmt_bind_param($stmt, 'ss', $studentId, $message);
					$ok = mysqli_stmt_execute($stmt);
					mysqli_stmt_close($stmt);
					$notice = $ok ? 'Your leave-hostel (pass-out) request was submitted for manager review.' : 'Unable to submit your leave-hostel request.';
					$noticeType = $ok ? 'success' : 'error';
				}
			} else {
				$requestedHostelId = filter_input(INPUT_POST, 'requested_hostel_id', FILTER_VALIDATE_INT);
				$message = trim(isset($_POST['message']) ? $_POST['message'] : '');
				$hostelStmt = mysqli_prepare($conn, 'SELECT Hostel_id FROM Hostel WHERE Hostel_id = ?');
				mysqli_stmt_bind_param($hostelStmt, 'i', $requestedHostelId);
				mysqli_stmt_execute($hostelStmt);
				$requestedHostel = mysqli_fetch_assoc(mysqli_stmt_get_result($hostelStmt));
				mysqli_stmt_close($hostelStmt);
				$pendingStmt = mysqli_prepare($conn, 'SELECT Application_id FROM Room_Change_Application WHERE Student_id = ? AND Status = 1 LIMIT 1');
				mysqli_stmt_bind_param($pendingStmt, 's', $studentId);
				mysqli_stmt_execute($pendingStmt);
				$pending = mysqli_num_rows(mysqli_stmt_get_result($pendingStmt)) > 0;
				mysqli_stmt_close($pendingStmt);
				if (!$requestedHostel) {
					$notice = 'Select a valid hostel for your room-change request.';
					$noticeType = 'error';
				} elseif ($pending) {
					$notice = 'You already have a pending room-change request.';
					$noticeType = 'error';
				} else {
					$stmt = mysqli_prepare($conn, 'INSERT INTO Room_Change_Application (Student_id, Current_room_id, Requested_hostel_id, Status, Message) VALUES (?, ?, ?, 1, ?)');
					mysqli_stmt_bind_param($stmt, 'siis', $studentId, $allocation['Room_id'], $requestedHostelId, $message);
					$ok = mysqli_stmt_execute($stmt);
					mysqli_stmt_close($stmt);
					$notice = $ok ? 'Your room-change request was submitted for manager review.' : 'Unable to submit your room-change request.';
					$noticeType = $ok ? 'success' : 'error';
				}
			}
		}
	} elseif ($action === 'update_profile') {
		$mobile = trim(isset($_POST['mobile']) ? $_POST['mobile'] : '');
		$department = trim(isset($_POST['department']) ? $_POST['department'] : '');
		$year = trim(isset($_POST['year_of_study']) ? $_POST['year_of_study'] : '');
		if ($mobile === '' || $department === '' || $year === '') {
			$notice = 'Please complete all profile fields.';
			$noticeType = 'error';
		} else {
			$stmt = mysqli_prepare($conn, 'UPDATE Student SET Mob_no = ?, Dept = ?, Year_of_study = ? WHERE Student_id = ?');
			mysqli_stmt_bind_param($stmt, 'ssss', $mobile, $department, $year, $studentId);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			if ($ok) {
				$notice = 'Your profile has been updated.';
			} else {
				$notice = 'Unable to update your profile. Please try again.';
				$noticeType = 'error';
			}
		}
	}
}

$hasPayment = dbTableExists($conn, 'Payment');
$hasRoomChangeRequests = dbTableExists($conn, 'Room_Change_Application');
$hasPassoutRequests = dbTableExists($conn, 'Passout_Application');
$hasMess = dbTableExists($conn, 'Mess') && dbColumnExists($conn, 'Student', 'Mess_id');
$hasMessCard = $hasMess && dbTableExists($conn, 'Mess_Allocation') && dbColumnExists($conn, 'Student', 'Mess_card_id');
$paymentJoin = $hasPayment ? ' LEFT JOIN Payment p ON p.Student_id = s.Student_id' : '';
$messJoin = $hasMess ? ' LEFT JOIN Mess m ON m.Mess_id = s.Mess_id' : '';
$messCardJoin = $hasMessCard ? ' LEFT JOIN Mess_Allocation ma ON ma.Mess_card_id = s.Mess_card_id' : '';
$managerJoin = dbTableExists($conn, 'Hostel_Manager') ? ' LEFT JOIN Hostel_Manager hm ON hm.Hostel_id = s.Hostel_id' . (dbColumnExists($conn, 'Hostel_Manager', 'Isadmin') ? ' AND hm.Isadmin = 0' : '') : '';
$hasPaymentProof = $hasPayment && dbColumnExists($conn, 'Payment', 'Proof_path');
$paymentFields = $hasPayment ? 'p.Status AS Payment_status, p.Amount AS Payment_amount' . ($hasPaymentProof ? ', p.Proof_path AS Payment_proof' : ', NULL AS Payment_proof') : 'NULL AS Payment_status, NULL AS Payment_amount, NULL AS Payment_proof';
$messFields = $hasMess ? 'm.Mess_name' : 'NULL AS Mess_name';
$messCardFields = $hasMessCard ? 'ma.Mess_card_No' : 'NULL AS Mess_card_No';
$managerFields = $managerJoin !== '' ? 'hm.Fname AS Manager_fname, hm.Lname AS Manager_lname, hm.Mob_no AS Manager_mobile' : 'NULL AS Manager_fname, NULL AS Manager_lname, NULL AS Manager_mobile';
$profileSql = 'SELECT s.Student_id, s.Fname, s.Lname, s.Mob_no, s.Dept, s.Year_of_study, s.Hostel_id, s.Room_id, h.Hostel_name, r.Room_No, ' . $messFields . ', ' . $paymentFields . ', ' . $messCardFields . ', ' . $managerFields . ' FROM Student s LEFT JOIN Hostel h ON h.Hostel_id = s.Hostel_id LEFT JOIN Room r ON r.Room_id = s.Room_id' . $messJoin . $paymentJoin . $messCardJoin . $managerJoin . ' WHERE s.Student_id = ?';
$stmt = mysqli_prepare($conn, $profileSql);
mysqli_stmt_bind_param($stmt, 's', $studentId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$student) {
	http_response_code(404);
	exit('Student profile not found.');
}

$roomApplications = mysqli_prepare($conn, 'SELECT a.Application_id, a.Application_status, a.Message, h.Hostel_name FROM Application a LEFT JOIN Hostel h ON h.Hostel_id = a.Hostel_id WHERE a.Student_id = ? ORDER BY a.Application_id DESC');
mysqli_stmt_bind_param($roomApplications, 's', $studentId);
mysqli_stmt_execute($roomApplications);
$roomApplicationRows = mysqli_stmt_get_result($roomApplications);
$messApplicationRows = null;
if ($hasMess && dbTableExists($conn, 'Application_mess')) {
	$messApplications = mysqli_prepare($conn, 'SELECT a.Application_status, a.Message, a.Mess_card_No, m.Mess_name FROM Application_mess a LEFT JOIN Mess m ON m.Mess_id = a.Mess_id WHERE a.Student_id = ? ORDER BY a.Application_id DESC');
	mysqli_stmt_bind_param($messApplications, 's', $studentId);
	mysqli_stmt_execute($messApplications);
	$messApplicationRows = mysqli_stmt_get_result($messApplications);
}
$roomChangeRequestRows = null;
if ($hasRoomChangeRequests) {
	$roomChangeRequests = mysqli_prepare($conn, 'SELECT a.Status, a.Message, a.Created_at, h.Hostel_name FROM Room_Change_Application a JOIN Hostel h ON h.Hostel_id = a.Requested_hostel_id WHERE a.Student_id = ? ORDER BY a.Application_id DESC');
	mysqli_stmt_bind_param($roomChangeRequests, 's', $studentId);
	mysqli_stmt_execute($roomChangeRequests);
	$roomChangeRequestRows = mysqli_stmt_get_result($roomChangeRequests);
}
$passoutRequestRows = null;
if ($hasPassoutRequests) {
	$passoutRequests = mysqli_prepare($conn, 'SELECT Status, Message, Created_at FROM Passout_Application WHERE Student_id = ? ORDER BY Application_id DESC');
	mysqli_stmt_bind_param($passoutRequests, 's', $studentId);
	mysqli_stmt_execute($passoutRequests);
	$passoutRequestRows = mysqli_stmt_get_result($passoutRequests);
}
$hostelOptions = mysqli_query($conn, 'SELECT Hostel_id, Hostel_name FROM Hostel ORDER BY Hostel_name');

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function statusLabel($status) {
	if ((int)$status === 1) return 'Pending';
	if ((int)$status === 0) return 'Approved';
	if ((int)$status === -1) return 'Rejected';
	return 'Unknown';
}
function statusClass($status) {
	if ((int)$status === 1) return 'pending';
	if ((int)$status === 0) return 'approved';
	if ((int)$status === -1) return 'rejected';
	return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My profile | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web_home/css_home/style.css">
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
	<link rel="stylesheet" href="web/css/student-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero">
		<div><p class="eyebrow">STUDENT PROFILE</p><h1><?php echo h($student['Fname'].' '.$student['Lname']); ?></h1><p>Manage your details, accommodation and requests in one place.</p></div>
		<div class="profile-roll"><span>Student ID</span><strong><?php echo h($student['Student_id']); ?></strong></div>
	</section>

	<?php if ($notice !== ''): ?><div class="notice <?php echo h($noticeType); ?>" role="status"><?php echo h($notice); ?></div><?php endif; ?>

	<div class="dashboard-grid">
		<section class="panel profile-details">
			<div class="panel-heading"><div><p class="eyebrow">YOUR INFORMATION</p><h2>Profile details</h2></div></div>
			<dl class="detail-grid">
				<div><dt>Full name</dt><dd><?php echo h($student['Fname'].' '.$student['Lname']); ?></dd></div>
				<div><dt>Student ID</dt><dd><?php echo h($student['Student_id']); ?></dd></div>
				<div><dt>Phone</dt><dd><?php echo h($student['Mob_no']); ?></dd></div>
				<div><dt>Department</dt><dd><?php echo h($student['Dept']); ?></dd></div>
				<div><dt>Year of study</dt><dd><?php echo h($student['Year_of_study']); ?></dd></div>
				<div><dt>Payment</dt><dd><?php echo !$hasPayment ? 'Not available' : ((int)$student['Payment_status'] === 1 && empty($student['Payment_proof']) ? 'Needs proof' : ((int)$student['Payment_status'] === 1 ? 'Approved' : ((int)$student['Payment_status'] === 2 ? 'Pending review' : ((int)$student['Payment_status'] === -1 ? 'Rejected' : 'Unpaid')))); ?></dd></div>
				<div><dt>Payment amount</dt><dd><?php echo (in_array((int)$student['Payment_status'], array(2, -1), true) || (int)$student['Payment_status'] === 1 && !empty($student['Payment_proof'])) && $student['Payment_amount'] !== null ? h(number_format((float)$student['Payment_amount'])) : 'Not submitted'; ?></dd></div>
				<div><dt>Hostel</dt><dd><?php echo h($student['Hostel_name'] ?: 'Not allocated'); ?></dd></div>
				<div><dt>Room number</dt><dd><?php echo h($student['Room_No'] ?: 'Not allocated'); ?></dd></div>
				<div><dt>Hostel manager</dt><dd><?php echo h(trim(($student['Manager_fname'] ?: '').' '.($student['Manager_lname'] ?: '')) ?: 'Not available'); ?></dd></div>
				<div><dt>Manager contact</dt><dd><?php echo h($student['Manager_mobile'] ?: 'Not available'); ?></dd></div>
				<div><dt>Mess</dt><dd><?php echo h($student['Mess_name'] ?: 'Not allocated'); ?></dd></div>
				<div><dt>Mess card</dt><dd><?php echo h($student['Mess_card_No'] ?: 'Not allocated'); ?></dd></div>
			</dl>
			<details class="edit-details"><summary>Edit contact and academic details</summary>
				<form method="post" class="request-form">
					<input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>"><input type="hidden" name="action" value="update_profile">
					<label>Phone number<input name="mobile" value="<?php echo h($student['Mob_no']); ?>" required maxlength="30"></label>
					<label>Department<input name="department" value="<?php echo h($student['Dept']); ?>" required maxlength="255"></label>
					<label>Year of study<input name="year_of_study" value="<?php echo h($student['Year_of_study']); ?>" required maxlength="30"></label>
					<button class="button" type="submit">Save profile</button>
				</form>
			</details>
		</section>

		<section class="panel">
			<div class="panel-heading"><div><p class="eyebrow">APPLICATIONS</p><h2>Hostel application status</h2></div></div>
			<div class="status-list">
				<?php if (mysqli_num_rows($roomApplicationRows) === 0): ?><p class="empty-state">You have not submitted a hostel application yet.</p><?php endif; ?>
				<?php while ($application = mysqli_fetch_assoc($roomApplicationRows)): ?>
				<article class="status-item"><div><strong><?php echo h($application['Hostel_name'] ?: 'Hostel'); ?></strong><p><?php echo h($application['Message'] ?: 'No additional message'); ?></p></div><span class="status-badge <?php echo h(statusClass($application['Application_status'])); ?>"><?php echo h(statusLabel($application['Application_status'])); ?></span></article>
				<?php endwhile; ?>
			</div>
			<a class="text-link" href="services.php">Apply for a hostel <span aria-hidden="true">&rarr;</span></a>
		</section>

		<?php if ($messApplicationRows !== null): ?>
		<section class="panel">
			<div class="panel-heading"><div><p class="eyebrow">DINING</p><h2>Mess application status</h2></div></div>
			<div class="status-list">
				<?php if (mysqli_num_rows($messApplicationRows) === 0): ?><p class="empty-state">You have not submitted a mess application yet.</p><?php endif; ?>
				<?php while ($application = mysqli_fetch_assoc($messApplicationRows)): ?><article class="status-item"><div><strong>Mess <?php echo h($application['Mess_name'] ?: ''); ?></strong><p><?php echo h($application['Message'] ?: 'No additional message'); ?><?php if (!empty($application['Mess_card_No'])): ?> · Card <?php echo (int)$application['Mess_card_No']; ?><?php endif; ?></p></div><span class="status-badge <?php echo h(statusClass($application['Application_status'])); ?>"><?php echo h(statusLabel($application['Application_status'])); ?></span></article><?php endwhile; ?>
			</div>
			<a class="text-link" href="services_mess.php">Browse mess options <span aria-hidden="true">&rarr;</span></a>
		</section>
		<?php endif; ?>

		<?php if ($hasRoomChangeRequests): ?>
		<section class="panel">
			<div class="panel-heading"><div><p class="eyebrow">ACCOMMODATION</p><h2>Change room or hostel</h2></div></div>
			<?php if (!$student['Room_id']): ?><p class="empty-state">A room must be allocated before requesting a change.</p><?php else: ?>
			<form method="post" class="request-form">
				<input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>"><input type="hidden" name="action" value="submit_room_change">
				<label>Requested hostel<select name="requested_hostel_id" required><?php while ($hostelOption = mysqli_fetch_assoc($hostelOptions)): ?><option value="<?php echo (int)$hostelOption['Hostel_id']; ?>" <?php echo (int)$hostelOption['Hostel_id'] === (int)$student['Hostel_id'] ? 'selected' : ''; ?>><?php echo h($hostelOption['Hostel_name']); ?></option><?php endwhile; ?></select></label>
				<label>Reason (optional)<textarea name="message" rows="3" maxlength="255"></textarea></label>
				<button class="button" type="submit">Request room change</button>
			</form>
			<?php endif; ?>
			<div class="status-list">
				<?php if (!$roomChangeRequestRows || mysqli_num_rows($roomChangeRequestRows) === 0): ?><p class="empty-state">No room-change requests yet.</p><?php endif; ?>
				<?php if ($roomChangeRequestRows): while ($request = mysqli_fetch_assoc($roomChangeRequestRows)): ?><article class="status-item"><div><strong><?php echo h($request['Hostel_name']); ?></strong><p><?php echo h($request['Message'] ?: 'No additional message'); ?> · <?php echo h($request['Created_at']); ?></p></div><span class="status-badge <?php echo h(statusClass($request['Status'])); ?>"><?php echo h(statusLabel($request['Status'])); ?></span></article><?php endwhile; endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ($hasPassoutRequests): ?>
		<section class="panel">
			<div class="panel-heading"><div><p class="eyebrow">LEAVE HOSTEL</p><h2>Pass-out request</h2></div></div>
			<p class="panel-description">Request to leave your current hostel. The allocation is released only after the manager approves.</p>
			<?php if (!$student['Room_id']): ?><p class="empty-state">You do not currently have an allocated hostel room.</p><?php else: ?><form method="post" class="request-form"><input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>"><input type="hidden" name="action" value="submit_passout"><label>Reason (optional)<textarea name="message" rows="3" maxlength="255"></textarea></label><button class="button" type="submit">Request to leave hostel</button></form><?php endif; ?>
			<div class="status-list">
				<?php if (!$passoutRequestRows || mysqli_num_rows($passoutRequestRows) === 0): ?><p class="empty-state">No leave-hostel requests yet.</p><?php endif; ?>
				<?php if ($passoutRequestRows): while ($request = mysqli_fetch_assoc($passoutRequestRows)): ?><article class="status-item"><div><strong>Leave hostel</strong><p><?php echo h($request['Message'] ?: 'No additional message'); ?> · <?php echo h($request['Created_at']); ?></p></div><span class="status-badge <?php echo h(statusClass($request['Status'])); ?>"><?php echo h(statusLabel($request['Status'])); ?></span></article><?php endwhile; endif; ?>
			</div>
		</section>
		<?php endif; ?>

	</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
