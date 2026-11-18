<?php
session_start();
require 'includes/config.inc.php';
require 'includes/manager_access.php';

$isAdmin = isset($_SESSION['isadmin']) && (int)$_SESSION['isadmin'] === 1;
$isManager = !empty($_SESSION['hostel_man_id']) && !$isAdmin;
if (!$isAdmin && !$isManager) {
	header('Location: login-hostel_manager.php');
	exit();
}
$manager = null;
if ($isManager) {
	$manager = requireManagerAccess($conn, array('Warden', 'Hostel Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');
}
$hostelId = isset($_SESSION['hostel_id']) ? (int)$_SESSION['hostel_id'] : 0;
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$notice = isset($_SESSION['complaint_admin_notice']) ? $_SESSION['complaint_admin_notice'] : '';
unset($_SESSION['complaint_admin_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
	$status = isset($_POST['status']) ? $_POST['status'] : '';
	$response = trim(isset($_POST['response']) ? $_POST['response'] : '');
	$validStatuses = array('Pending', 'In progress', 'Resolved');
	if (!$complaintId || !in_array($status, $validStatuses, true) || strlen($response) > 5000) {
		http_response_code(400);
		exit('Invalid complaint update.');
	}
	if ($isAdmin) {
		$stmt = mysqli_prepare($conn, 'UPDATE Complaint SET Status = ?, Response = ?, Updated_at = CURRENT_TIMESTAMP WHERE Complaint_id = ?');
		mysqli_stmt_bind_param($stmt, 'ssi', $status, $response, $complaintId);
	} else {
		$stmt = mysqli_prepare($conn, 'UPDATE Complaint SET Status = ?, Response = ?, Updated_at = CURRENT_TIMESTAMP WHERE Complaint_id = ? AND Hostel_id = ?');
		mysqli_stmt_bind_param($stmt, 'ssii', $status, $response, $complaintId, $hostelId);
	}
	$ok = mysqli_stmt_execute($stmt);
	$affectedRows = mysqli_stmt_affected_rows($stmt);
	mysqli_stmt_close($stmt);
	$_SESSION['complaint_admin_notice'] = $ok && ($affectedRows > 0) ? 'Complaint updated.' : 'The complaint could not be updated or is outside your access.';
	header('Location: manage_complaints.php');
	exit();
}

if ($isAdmin) {
	$complaints = mysqli_query($conn, "SELECT c.Complaint_id, c.Subject, c.Details, c.Status, c.Response, c.Created_at, s.Student_id, s.Fname, s.Lname, h.Hostel_name FROM Complaint c JOIN Student s ON s.Student_id = c.Student_id LEFT JOIN Hostel h ON h.Hostel_id = c.Hostel_id ORDER BY FIELD(c.Status, 'Pending', 'In progress', 'Resolved'), c.Created_at DESC");
} else {
	$stmt = mysqli_prepare($conn, "SELECT c.Complaint_id, c.Subject, c.Details, c.Status, c.Response, c.Created_at, s.Student_id, s.Fname, s.Lname, h.Hostel_name FROM Complaint c JOIN Student s ON s.Student_id = c.Student_id LEFT JOIN Hostel h ON h.Hostel_id = c.Hostel_id WHERE c.Hostel_id = ? ORDER BY FIELD(c.Status, 'Pending', 'In progress', 'Resolved'), c.Created_at DESC");
	mysqli_stmt_bind_param($stmt, 'i', $hostelId);
	mysqli_stmt_execute($stmt);
	$complaints = mysqli_stmt_get_result($stmt);
}
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Manage complaints | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"><link rel="stylesheet" href="web_home/css_home/style.css"><link rel="stylesheet" href="web/css/student-dashboard.css"><link rel="stylesheet" href="web/css/admin-dashboard.css"><link rel="stylesheet" href="web/css/staff-dashboard.css?v=1">
</head>
<body class="student-dashboard<?php echo $isAdmin ? '' : ' staff-dashboard'; ?>">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow"><?php echo $isAdmin ? 'ADMINISTRATION' : 'HOSTEL MANAGER'; ?></p><h1>Complaint inbox</h1><p><?php echo $isAdmin ? 'Review and respond to all student complaints.' : 'Review and respond to complaints for your hostel.'; ?></p></div></section>
	<?php if ($notice !== ''): ?><div class="notice success" role="status"><?php echo h($notice); ?></div><?php endif; ?>
	<div class="admin-request-list">
		<?php if (mysqli_num_rows($complaints) === 0): ?><section class="panel"><p class="empty-state">There are no complaints to review.</p></section><?php endif; ?>
		<?php while ($complaint = mysqli_fetch_assoc($complaints)): ?>
		<section class="admin-request-card">
			<div class="status-item"><div><h3><?php echo h($complaint['Subject']); ?></h3><p class="request-info"><?php echo h($complaint['Fname'].' '.$complaint['Lname']); ?> · <?php echo h($complaint['Student_id']); ?> · <?php echo h($complaint['Hostel_name'] ?: 'No hostel assigned'); ?><br>Submitted <?php echo h($complaint['Created_at']); ?></p></div><span class="status-badge <?php echo $complaint['Status'] === 'Resolved' ? 'approved' : ($complaint['Status'] === 'In progress' ? 'pending' : 'rejected'); ?>"><?php echo h($complaint['Status']); ?></span></div>
			<p class="request-text"><?php echo h($complaint['Details']); ?></p>
			<form method="post" class="admin-response-form">
				<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>"><input type="hidden" name="complaint_id" value="<?php echo (int)$complaint['Complaint_id']; ?>">
				<label>Status<select name="status"><?php foreach (array('Pending', 'In progress', 'Resolved') as $option): ?><option value="<?php echo h($option); ?>" <?php echo $complaint['Status'] === $option ? 'selected' : ''; ?>><?php echo h($option); ?></option><?php endforeach; ?></select></label>
				<label>Response to student<textarea name="response" maxlength="5000" placeholder="Add a response or resolution note"><?php echo h($complaint['Response']); ?></textarea></label>
				<button class="button" type="submit">Save response</button>
			</form>
		</section>
		<?php endwhile; ?>
	</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
