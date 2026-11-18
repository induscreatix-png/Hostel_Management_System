<?php
session_start();
require '../includes/config.inc.php';
require '../includes/manager_access.php';

$manager = requireManagerAccess($conn, array('Warden', 'Hostel Manager', 'Mess Manager', 'Finance Reviewer'), '../login-hostel_manager.php', 'admin_home.php');
$profileStmt = mysqli_prepare($conn, 'SELECT hm.Username, hm.Fname, hm.Lname, hm.Mob_no, hm.Position, h.Hostel_name, m.Mess_name, CASE WHEN h.Hostel_id IS NOT NULL AND h.No_of_rooms > 0 THEN ROUND(100 * h.current_no_of_rooms / h.No_of_rooms, 1) ELSE NULL END AS Hostel_Occupancy, CASE WHEN m.Mess_id IS NOT NULL AND m.Size > 0 THEN ROUND(100 * (m.Size - m.Vacancy) / m.Size, 1) ELSE NULL END AS Mess_Occupancy FROM Hostel_Manager hm LEFT JOIN Hostel h ON h.Hostel_id = hm.Hostel_id LEFT JOIN Mess m ON m.Mess_id = hm.Mess_id WHERE hm.Hostel_man_id = ? LIMIT 1');
if (!$profileStmt) {
	http_response_code(500);
	exit('Could not load your profile.');
}
mysqli_stmt_bind_param($profileStmt, 'i', $manager['Hostel_man_id']);
if (!mysqli_stmt_execute($profileStmt)) {
	mysqli_stmt_close($profileStmt);
	http_response_code(500);
	exit('Could not load your profile.');
}
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStmt));
mysqli_stmt_close($profileStmt);
if (!$row) {
	http_response_code(404);
	exit('Staff profile not found.');
}

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$fullName = trim($row['Fname'] . ' ' . $row['Lname']);
$position = $row['Position'] ?: $manager['Position'];
$assignedArea = $row['Hostel_name'] !== null ? 'Hostel ' . $row['Hostel_name'] : ($row['Mess_name'] !== null ? 'Mess ' . $row['Mess_name'] : ($position === 'Finance Reviewer' ? 'Payment review' : 'Not assigned'));
$initials = strtoupper(substr($row['Fname'], 0, 1) . substr($row['Lname'], 0, 1));
if ($initials === '') {
	$initials = 'ST';
}
$hostelOccupancy = $row['Hostel_Occupancy'] === null ? null : min(100, max(0, (float)$row['Hostel_Occupancy']));
$messOccupancy = $row['Mess_Occupancy'] === null ? null : min(100, max(0, (float)$row['Mess_Occupancy']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Staff profile | Nayab Hostel</title>
	<link rel="stylesheet" href="../web/css/student-dashboard.css">
	<link rel="stylesheet" href="../web/css/staff-dashboard.css?v=1">
</head>
<body class="student-dashboard staff-dashboard staff-profile-page">
<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero staff-profile-hero">
		<div class="staff-profile-identity">
			<div class="staff-avatar" aria-hidden="true"><?php echo h($initials); ?></div>
			<div>
				<p class="eyebrow"><?php echo h($position); ?></p>
				<h1><?php echo h($fullName); ?></h1>
				<p>Staff profile and assigned service area.</p>
			</div>
		</div>
		<div class="profile-roll">
			<span>Username</span>
			<strong><?php echo h($row['Username']); ?></strong>
		</div>
	</section>

	<div class="dashboard-grid staff-profile-grid">
		<section class="panel staff-profile-card">
			<div class="panel-heading"><div><p class="eyebrow">ACCOUNT</p><h2>Personal details</h2></div></div>
			<dl class="detail-grid">
				<div><dt>Full name</dt><dd><?php echo h($fullName); ?></dd></div>
				<div><dt>Username</dt><dd><?php echo h($row['Username']); ?></dd></div>
				<div><dt>Mobile number</dt><dd><?php echo h($row['Mob_no']); ?></dd></div>
				<div><dt>Staff role</dt><dd><?php echo h($position); ?></dd></div>
			</dl>
		</section>

		<section class="panel staff-area-panel">
			<div class="panel-heading"><div><p class="eyebrow">RESPONSIBILITY</p><h2>Assigned area</h2></div></div>
			<p class="staff-assigned-area"><?php echo h($assignedArea); ?></p>
			<?php if ($hostelOccupancy !== null): ?>
				<div class="staff-occupancy">
					<div><span>Hostel occupancy</span><strong><?php echo h($hostelOccupancy); ?>%</strong></div>
					<div class="staff-occupancy-track" role="progressbar" aria-label="Hostel occupancy" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo h($hostelOccupancy); ?>"><span style="width: <?php echo h($hostelOccupancy); ?>%"></span></div>
				</div>
			<?php elseif ($messOccupancy !== null): ?>
				<div class="staff-occupancy">
					<div><span>Mess occupancy</span><strong><?php echo h($messOccupancy); ?>%</strong></div>
					<div class="staff-occupancy-track" role="progressbar" aria-label="Mess occupancy" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo h($messOccupancy); ?>"><span style="width: <?php echo h($messOccupancy); ?>%"></span></div>
				</div>
			<?php else: ?>
				<p class="panel-description">Your account is limited to the duties assigned to this role.</p>
			<?php endif; ?>
			<a class="button staff-profile-back" href="../home_manager.php">Back to dashboard</a>
		</section>
	</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
