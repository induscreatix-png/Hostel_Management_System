<?php
session_start();
require '../includes/config.inc.php';

if (empty($_SESSION['hostel_man_id']) || !isset($_SESSION['isadmin']) || (int)$_SESSION['isadmin'] !== 1) {
	header('Location: ../login-hostel_manager.php');
	exit();
}
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$hostels = mysqli_query($conn, 'SELECT Hostel_id, Hostel_name FROM Hostel ORDER BY Hostel_name');
$messes = mysqli_query($conn, 'SELECT Mess_id, Mess_name FROM Mess ORDER BY Mess_name');
$staff = mysqli_query($conn, 'SELECT hm.Hostel_man_id, hm.Username, hm.Fname, hm.Lname, hm.Mob_no, hm.Position, h.Hostel_name, m.Mess_name FROM Hostel_Manager hm LEFT JOIN Hostel h ON h.Hostel_id = hm.Hostel_id LEFT JOIN Mess m ON m.Mess_id = hm.Mess_id WHERE hm.Isadmin = 0 ORDER BY hm.Position, hm.Username');
$staffStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(Position IN ('Warden', 'Hostel Manager')) AS hostel_staff, SUM(Position = 'Mess Manager') AS mess_staff, SUM(Position = 'Finance Reviewer') AS finance_staff FROM Hostel_Manager WHERE Isadmin = 0"));
$staffNotice = isset($_SESSION['staff_notice']) ? $_SESSION['staff_notice'] : '';
$staffNoticeType = isset($_SESSION['staff_notice_type']) ? $_SESSION['staff_notice_type'] : 'success';
unset($_SESSION['staff_notice'], $_SESSION['staff_notice_type']);

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Staff access | Nayab Hostel</title>
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="../web/css/student-dashboard.css">
	<link rel="stylesheet" href="../web/css/admin-dashboard.css">
	<link rel="stylesheet" href="../web/css/staff-dashboard.css?v=1">
</head>
<body class="student-dashboard staff-access-page">
<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero">
		<div><p class="eyebrow">ADMINISTRATION</p><h1>Staff access management</h1><p>Create accounts with access limited to their assigned hostel, mess, or finance duties.</p></div>
	</section>
	<?php if ($staffNotice !== ''): ?><div class="notice <?php echo $staffNoticeType === 'error' ? 'error' : 'success'; ?>" role="status"><?php echo h($staffNotice); ?></div><?php endif; ?>
	<div class="admin-stats">
		<div class="admin-stat"><span>Staff accounts</span><strong><?php echo (int)$staffStats['total']; ?></strong></div>
		<div class="admin-stat"><span>Hostel staff</span><strong><?php echo (int)$staffStats['hostel_staff']; ?></strong></div>
		<div class="admin-stat"><span>Mess staff</span><strong><?php echo (int)$staffStats['mess_staff']; ?></strong></div>
		<div class="admin-stat"><span>Finance reviewers</span><strong><?php echo (int)$staffStats['finance_staff']; ?></strong></div>
	</div>
	<section class="panel">
		<div class="panel-heading"><div><p class="eyebrow">NEW ACCOUNT</p><h2>Appoint a staff member</h2></div></div>
		<p class="panel-description">Hostel roles require one hostel assignment. Mess Managers require one mess assignment. Finance Reviewers are limited to payment review.</p>
		<form class="request-form" action="../includes/hm_signup.php" method="post">
			<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
			<div class="staff-form-grid">
				<label>Username<input type="text" name="hm_uname" autocomplete="username" pattern="[A-Za-z0-9]+" required></label>
				<label>Position<select id="position" name="position" required><option value="">Select a role</option><option value="Warden">Warden</option><option value="Hostel Manager">Hostel Manager</option><option value="Mess Manager">Mess Manager</option><option value="Finance Reviewer">Finance Reviewer</option></select></label>
				<label>First name<input type="text" name="hm_fname" autocomplete="given-name" required></label>
				<label>Last name<input type="text" name="hm_lname" autocomplete="family-name" required></label>
				<label>Mobile number<input type="tel" name="hm_mobile" autocomplete="tel" required></label>
				<div id="hostel-assignment" class="staff-assignment">
					<label>Assigned hostel<select name="hostel_id" id="hostel-id"><option value="">Select a hostel</option><?php while ($hostel = mysqli_fetch_assoc($hostels)): ?><option value="<?php echo (int)$hostel['Hostel_id']; ?>"><?php echo h($hostel['Hostel_name']); ?></option><?php endwhile; ?></select></label>
				</div>
				<div id="mess-assignment" class="staff-assignment">
					<label>Assigned mess<select name="mess_id" id="mess-id"><option value="">Select a mess</option><?php while ($mess = mysqli_fetch_assoc($messes)): ?><option value="<?php echo (int)$mess['Mess_id']; ?>"><?php echo h($mess['Mess_name']); ?></option><?php endwhile; ?></select></label>
				</div>
				<label>Password<input type="password" name="pass" autocomplete="new-password" minlength="8" required></label>
				<label>Confirm password<input type="password" name="confpass" autocomplete="new-password" minlength="8" required></label>
			</div>
			<button class="button" type="submit" name="hm_signup_submit" value="1">Create staff account</button>
		</form>
	</section>
	<section class="panel directory-panel">
		<div class="panel-heading"><div><p class="eyebrow">ACCESS DIRECTORY</p><h2>Current staff</h2></div></div>
		<div class="table-responsive">
			<table class="table admin-table staff-table">
				<thead><tr><th>Username</th><th>Name</th><th>Position</th><th>Assigned area</th><th>Mobile</th><th>Action</th></tr></thead>
				<tbody>
					<?php if (!$staff || mysqli_num_rows($staff) === 0): ?><tr><td colspan="6" class="empty-state">No staff accounts found.</td></tr><?php endif; ?>
					<?php if ($staff): while ($person = mysqli_fetch_assoc($staff)):
						$positionName = $person['Position'] ?: 'Hostel Manager';
						if (in_array($positionName, array('Warden', 'Hostel Manager'), true)) {
							$assignedArea = $person['Hostel_name'] ? 'Hostel ' . $person['Hostel_name'] : 'No hostel assigned';
						} elseif ($positionName === 'Mess Manager') {
							$assignedArea = $person['Mess_name'] ? 'Mess ' . $person['Mess_name'] : 'No mess assigned';
						} else {
							$assignedArea = 'Payment review';
						}
					?>
					<tr>
						<td><?php echo h($person['Username']); ?></td>
						<td><?php echo h($person['Fname'] . ' ' . $person['Lname']); ?></td>
						<td><span class="role-pill"><?php echo h($positionName); ?></span></td>
						<td><?php echo h($assignedArea); ?></td>
						<td><?php echo h($person['Mob_no']); ?></td>
						<td><form class="staff-remove" action="../includes/hm_remove.php" method="post" onsubmit="return confirm('Remove this staff account?');"><input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>"><input type="hidden" name="manager_id" value="<?php echo (int)$person['Hostel_man_id']; ?>"><button class="button button-outline" type="submit" name="hm_remove_submit" value="1">Remove</button></form></td>
					</tr>
					<?php endwhile; endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
(function () {
	var position = document.getElementById('position');
	var hostelGroup = document.getElementById('hostel-assignment');
	var messGroup = document.getElementById('mess-assignment');
	var hostelSelect = document.getElementById('hostel-id');
	var messSelect = document.getElementById('mess-id');
	function updateAssignments() {
		var isHostelRole = position.value === 'Warden' || position.value === 'Hostel Manager';
		var isMessRole = position.value === 'Mess Manager';
		hostelGroup.hidden = !isHostelRole;
		messGroup.hidden = !isMessRole;
		hostelSelect.disabled = !isHostelRole;
		messSelect.disabled = !isMessRole;
		hostelSelect.required = isHostelRole;
		messSelect.required = isMessRole;
	}
	position.addEventListener('change', updateAssignments);
	updateAssignments();
}());
</script>
</body>
</html>
