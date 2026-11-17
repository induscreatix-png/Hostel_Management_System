<?php
session_start();
require '../includes/config.inc.php';

if (empty($_SESSION['hostel_man_id']) || !isset($_SESSION['isadmin']) || (int)$_SESSION['isadmin'] !== 1) {
	header('Location: ../login-hostel_manager.php');
	exit();
}

$totalStudents = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Student'))['total'];
$allocatedStudents = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM Student WHERE Room_id IS NOT NULL'))['total'];
$students = mysqli_query($conn, 'SELECT s.Student_id, s.Fname, s.Lname, s.Mob_no, s.Dept, s.Year_of_study, h.Hostel_name, r.Room_No FROM Student s LEFT JOIN Hostel h ON h.Hostel_id = s.Hostel_id LEFT JOIN Room r ON r.Room_id = s.Room_id ORDER BY h.Hostel_name, r.Room_No, s.Student_id');
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Students | Nayab Hostel</title>
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"><link rel="stylesheet" href="../web_home/css_home/style.css"><link rel="stylesheet" href="../web/css/student-dashboard.css"><link rel="stylesheet" href="../web/css/admin-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow">ADMINISTRATION</p><h1>Student directory</h1><p>Browse students grouped by their allocated hostel and room.</p></div></section>
	<div class="admin-stats">
		<div class="admin-stat"><span>Total students</span><strong><?php echo $totalStudents; ?></strong></div>
		<div class="admin-stat"><span>Students with rooms</span><strong><?php echo $allocatedStudents; ?></strong></div>
		<div class="admin-stat"><span>Not yet allocated</span><strong><?php echo max(0, $totalStudents - $allocatedStudents); ?></strong></div>
	</div>
	<section class="panel directory-panel">
		<div class="panel-heading"><div><p class="eyebrow">ROOM-WISE LIST</p><h2>Students by hostel and room</h2></div></div>
		<div class="table-responsive">
			<table class="table admin-table">
				<thead><tr><th>Hostel</th><th>Room</th><th>Student</th><th>Student ID</th><th>Phone</th><th>Department</th><th>Year</th></tr></thead>
				<tbody>
				<?php if (mysqli_num_rows($students) === 0): ?><tr><td colspan="7" class="empty-state">No student records found.</td></tr><?php endif; ?>
				<?php
				$lastRoom = null;
				while ($student = mysqli_fetch_assoc($students)):
					$roomKey = ($student['Hostel_name'] ?: 'Unassigned') . '|' . ($student['Room_No'] ?: 'Unassigned');
					if ($roomKey !== $lastRoom):
						$lastRoom = $roomKey;
				?>
					<tr class="room-group"><td><?php echo h($student['Hostel_name'] ?: 'Unassigned'); ?></td><td><?php echo h($student['Room_No'] ?: 'Unassigned'); ?></td><td colspan="5">Room roster</td></tr>
				<?php endif; ?>
					<tr><td><?php echo h($student['Hostel_name'] ?: '-'); ?></td><td><?php echo h($student['Room_No'] ?: '-'); ?></td><td><?php echo h($student['Fname'].' '.$student['Lname']); ?></td><td><?php echo h($student['Student_id']); ?></td><td><?php echo h($student['Mob_no']); ?></td><td><?php echo h($student['Dept']); ?></td><td><?php echo h($student['Year_of_study']); ?></td></tr>
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
