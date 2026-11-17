<?php
session_start();
require '../includes/config.inc.php';

if (empty($_SESSION['hostel_man_id']) || !isset($_SESSION['isadmin']) || (int)$_SESSION['isadmin'] !== 1) {
	header('Location: ../login-hostel_manager.php');
	exit();
}

$totalStudents = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Student'))['total'];
$allocatedStudents = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Student WHERE Room_id IS NOT NULL'))['total'];
$openComplaints = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Complaint WHERE Status <> 'Resolved'"))['total'];
$paidStudents = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT Student_id) AS total FROM Payment WHERE Status = 1 AND Proof_path IS NOT NULL AND Proof_path <> ''"))['total'];
$pendingRoomRequests = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Application WHERE Application_status = 1'))['total'];
$pendingMessRequests = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Application_mess WHERE Application_status = 1'))['total'];
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin dashboard | Nayab Hostel</title>
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"><link rel="stylesheet" href="../web_home/css_home/style.css"><link rel="stylesheet" href="../web/css/student-dashboard.css"><link rel="stylesheet" href="../web/css/admin-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow">ADMINISTRATION</p><h1>Welcome, <?php echo h($_SESSION['fname']); ?></h1><p>Monitor occupancy, payments and student support.</p></div></section>
	<div class="admin-stats">
		<div class="admin-stat"><span>Total students</span><strong><?php echo $totalStudents; ?></strong></div>
		<div class="admin-stat"><span>Students with room allocation</span><strong><?php echo $allocatedStudents; ?></strong></div>
		<div class="admin-stat"><span>Fees marked paid</span><strong><?php echo $paidStudents; ?></strong></div>
		<div class="admin-stat"><span>Open complaints</span><strong><?php echo $openComplaints; ?></strong></div>
	</div>
	<div class="admin-action-grid">
		<a class="admin-action" href="students.php"><strong>Student directory</strong><span>Browse total student counts and room-wise rosters.</span></a>
		<a class="admin-action" href="room_requests.php"><strong>Hostel room requests <span class="action-count"><?php echo $pendingRoomRequests; ?></span></strong><span>Review and allocate rooms for student applications.</span></a>
		<a class="admin-action" href="mess_requests.php"><strong>Mess applications <span class="action-count"><?php echo $pendingMessRequests; ?></span></strong><span>Review requests and allocate available mess cards.</span></a>
		<a class="admin-action" href="../manage_complaints.php"><strong>Complaint inbox <span class="action-count"><?php echo $openComplaints; ?></span></strong><span>Review, respond to, and resolve student complaints.</span></a>
		<a class="admin-action" href="payments.php"><strong>Payments</strong><span>Review paid and unpaid student fees.</span></a>
		<a class="admin-action" href="../services_mess.php"><strong>Mess options</strong><span>View available messes and student application options.</span></a>
		<a class="admin-action" href="create_hm.php"><strong>Staff access</strong><span>Create role-based staff accounts for hostels, messes, and finance.</span></a>
	</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
