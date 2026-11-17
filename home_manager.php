<?php
session_start();
require 'includes/config.inc.php';
require 'includes/manager_access.php';

$manager = requireManagerAccess($conn, array('Warden', 'Hostel Manager', 'Mess Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');
$position = $manager['Position'];
$isHostelStaff = in_array($position, array('Warden', 'Hostel Manager'), true);
$hostelSummary = null;
$messSummary = null;
$pendingRequests = 0;

if ($isHostelStaff) {
	$hostelId = (int)$manager['Hostel_id'];
	$stmt = mysqli_prepare($conn, 'SELECT h.Hostel_name, COUNT(r.Room_id) AS Room_count, COALESCE(SUM(r.Allocated), 0) AS Student_count, COALESCE(SUM(r.Capacity), 0) AS Bed_count, COALESCE(SUM(r.Capacity - r.Allocated), 0) AS Available_beds FROM Hostel h LEFT JOIN Room r ON r.Hostel_id = h.Hostel_id WHERE h.Hostel_id = ? GROUP BY h.Hostel_id, h.Hostel_name');
	if (!$stmt) {
		throw new RuntimeException(mysqli_error($conn));
	}
	mysqli_stmt_bind_param($stmt, 'i', $hostelId);
	mysqli_stmt_execute($stmt);
	$hostelSummary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);

	$stmt = mysqli_prepare($conn, 'SELECT (SELECT COUNT(*) FROM Room_Change_Application WHERE Status = 1 AND Requested_hostel_id = ?) + (SELECT COUNT(*) FROM Passout_Application p JOIN Student s ON s.Student_id = p.Student_id WHERE p.Status = 1 AND s.Hostel_id = ?) AS total');
	if (!$stmt) {
		throw new RuntimeException(mysqli_error($conn));
	}
	mysqli_stmt_bind_param($stmt, 'ii', $hostelId, $hostelId);
	mysqli_stmt_execute($stmt);
	$pendingRequests = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
	mysqli_stmt_close($stmt);
} else {
	$messId = (int)$manager['Mess_id'];
	$stmt = mysqli_prepare($conn, 'SELECT m.Mess_name, m.Size, m.Vacancy, COUNT(ma.Mess_card_id) AS Total_cards, COALESCE(SUM(ma.Allocated), 0) AS Allocated_cards FROM Mess m LEFT JOIN Mess_Allocation ma ON ma.Mess_id = m.Mess_id WHERE m.Mess_id = ? GROUP BY m.Mess_id, m.Mess_name, m.Size, m.Vacancy');
	if (!$stmt) {
		throw new RuntimeException(mysqli_error($conn));
	}
	mysqli_stmt_bind_param($stmt, 'i', $messId);
	mysqli_stmt_execute($stmt);
	$messSummary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);

	$stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM Application_mess WHERE Application_status = 1 AND Mess_id = ?');
	if (!$stmt) {
		throw new RuntimeException(mysqli_error($conn));
	}
	mysqli_stmt_bind_param($stmt, 'i', $messId);
	mysqli_stmt_execute($stmt);
	$pendingRequests = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
	mysqli_stmt_close($stmt);
}

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Staff dashboard | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web_home/css_home/style.css">
	<link rel="stylesheet" href="web/css/student-dashboard.css">
	<link rel="stylesheet" href="web/css/admin-dashboard.css">
	<style>
		.staff-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
		.staff-summary .admin-stat { min-width:0; }
		@media(max-width:760px) { .staff-summary { grid-template-columns:1fr; } }
	</style>
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero">
		<div><p class="eyebrow"><?php echo h($position); ?></p><h1>Staff dashboard</h1><p>Welcome, <?php echo h(trim($_SESSION['fname'] . ' ' . $_SESSION['lname'])); ?>. Manage only your assigned <?php echo $isHostelStaff ? 'hostel' : 'mess'; ?>.</p></div>
	</section>

	<?php if ($isHostelStaff && $hostelSummary): ?>
		<section class="staff-summary" aria-label="Hostel overview">
			<div class="admin-stat"><span>Assigned hostel</span><strong><?php echo h($hostelSummary['Hostel_name']); ?></strong></div>
			<div class="admin-stat"><span>Rooms and residents</span><strong><?php echo (int)$hostelSummary['Room_count']; ?> · <?php echo (int)$hostelSummary['Student_count']; ?></strong></div>
			<div class="admin-stat"><span>Available beds</span><strong><?php echo (int)$hostelSummary['Available_beds']; ?> / <?php echo (int)$hostelSummary['Bed_count']; ?></strong></div>
		</section>
		<div class="admin-action-grid">
			<a class="admin-action" href="allocate_room.php"><strong>Room allocation</strong><span>Allocate a student to an available bed in your hostel.</span></a>
			<a class="admin-action" href="manage_student_requests.php"><strong>Student requests <span class="action-count"><?php echo $pendingRequests; ?></span></strong><span>Review room-change and pass-out requests for your hostel.</span></a>
			<a class="admin-action" href="manage_complaints.php"><strong>Complaint inbox</strong><span>Review student complaints for your assigned hostel.</span></a>
			<a class="admin-action" href="allocated_rooms.php"><strong>Allocated rooms</strong><span>View room assignments and student rosters.</span></a>
			<a class="admin-action" href="empty_rooms.php"><strong>Available beds</strong><span>Check rooms with capacity remaining.</span></a>
			<a class="admin-action" href="vacate_rooms.php"><strong>Vacate a room</strong><span>Release a student allocation when needed.</span></a>
		</div>
	<?php elseif (!$isHostelStaff && $messSummary): ?>
		<section class="staff-summary" aria-label="Mess overview">
			<div class="admin-stat"><span>Assigned mess</span><strong><?php echo h($messSummary['Mess_name']); ?></strong></div>
			<div class="admin-stat"><span>Cards allocated</span><strong><?php echo (int)$messSummary['Allocated_cards']; ?> / <?php echo (int)$messSummary['Total_cards']; ?></strong></div>
			<div class="admin-stat"><span>Available cards</span><strong><?php echo (int)$messSummary['Vacancy']; ?></strong></div>
		</section>
		<div class="admin-action-grid">
			<a class="admin-action" href="allocate_mess_card.php"><strong>Mess allocation</strong><span>Assign an available card from your mess.</span></a>
			<a class="admin-action" href="manage_student_requests.php"><strong>Student requests <span class="action-count"><?php echo $pendingRequests; ?></span></strong><span>Review pending applications for your assigned mess.</span></a>
			<a class="admin-action" href="allocated_mess_card.php"><strong>Allocated cards</strong><span>View students currently assigned to your mess.</span></a>
			<a class="admin-action" href="vacate_mess.php"><strong>Release a card</strong><span>Remove an existing mess-card allocation.</span></a>
		</div>
	<?php else: ?>
		<div class="notice error" role="alert">Your assigned area could not be loaded. Contact an administrator.</div>
	<?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
