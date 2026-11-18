<?php
session_start();
require 'includes/config.inc.php';
require 'includes/manager_access.php';

$manager = requireManagerAccess($conn, array('Warden', 'Hostel Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');
$hostelId = (int)$manager['Hostel_id'];
$searchTerm = isset($_POST['search_box']) ? trim((string)$_POST['search_box']) : '';
$isSearch = isset($_POST['search']);
$hostelName = 'Hostel #' . $hostelId;

$hostelStmt = mysqli_prepare($conn, 'SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1');
if (!$hostelStmt) {
	throw new RuntimeException(mysqli_error($conn));
}
mysqli_stmt_bind_param($hostelStmt, 'i', $hostelId);
if (!mysqli_stmt_execute($hostelStmt)) {
	$error = mysqli_stmt_error($hostelStmt);
	mysqli_stmt_close($hostelStmt);
	throw new RuntimeException($error);
}
$hostel = mysqli_fetch_assoc(mysqli_stmt_get_result($hostelStmt));
mysqli_stmt_close($hostelStmt);
if ($hostel) {
	$hostelName = $hostel['Hostel_name'];
}

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$yearStmt = mysqli_prepare($conn, 'SELECT Year_of_study, COUNT(*) AS total_students FROM Student WHERE Hostel_id = ? GROUP BY Year_of_study ORDER BY total_students DESC, Year_of_study');
if (!$yearStmt) {
	throw new RuntimeException(mysqli_error($conn));
}
mysqli_stmt_bind_param($yearStmt, 'i', $hostelId);
if (!mysqli_stmt_execute($yearStmt)) {
	$error = mysqli_stmt_error($yearStmt);
	mysqli_stmt_close($yearStmt);
	throw new RuntimeException($error);
}
$yearResult = mysqli_stmt_get_result($yearStmt);
$yearBreakdown = array();
while ($row = mysqli_fetch_assoc($yearResult)) {
	$yearBreakdown[] = $row;
}
mysqli_stmt_close($yearStmt);

$departmentStmt = mysqli_prepare($conn, 'SELECT Dept, COUNT(*) AS total_students FROM Student WHERE Hostel_id = ? GROUP BY Dept ORDER BY total_students DESC, Dept');
if (!$departmentStmt) {
	throw new RuntimeException(mysqli_error($conn));
}
mysqli_stmt_bind_param($departmentStmt, 'i', $hostelId);
if (!mysqli_stmt_execute($departmentStmt)) {
	$error = mysqli_stmt_error($departmentStmt);
	mysqli_stmt_close($departmentStmt);
	throw new RuntimeException($error);
}
$departmentResult = mysqli_stmt_get_result($departmentStmt);
$departmentBreakdown = array();
while ($row = mysqli_fetch_assoc($departmentResult)) {
	$departmentBreakdown[] = $row;
}
mysqli_stmt_close($departmentStmt);

$studentSql = 'SELECT s.Student_id, s.Fname, s.Lname, s.Mob_no, s.Room_id, h.Hostel_name, r.Room_No, r.Allocated, r.Capacity FROM Student s LEFT JOIN Hostel h ON h.Hostel_id = s.Hostel_id LEFT JOIN Room r ON r.Room_id = s.Room_id WHERE s.Hostel_id = ?';
if ($isSearch) {
	$studentSql .= ' AND s.Student_id LIKE ?';
}
$studentSql .= ' ORDER BY r.Room_No, s.Student_id';
$studentStmt = mysqli_prepare($conn, $studentSql);
if (!$studentStmt) {
	throw new RuntimeException(mysqli_error($conn));
}
if ($isSearch) {
	$searchPattern = $searchTerm . '%';
	mysqli_stmt_bind_param($studentStmt, 'is', $hostelId, $searchPattern);
} else {
	mysqli_stmt_bind_param($studentStmt, 'i', $hostelId);
}
if (!mysqli_stmt_execute($studentStmt)) {
	$error = mysqli_stmt_error($studentStmt);
	mysqli_stmt_close($studentStmt);
	throw new RuntimeException($error);
}
$studentResult = mysqli_stmt_get_result($studentStmt);
$students = array();
while ($row = mysqli_fetch_assoc($studentResult)) {
	$students[] = $row;
}
mysqli_stmt_close($studentStmt);

$yearNames = array('1' => 'First', '2' => 'Second', '3' => 'Third', '4' => 'Fourth');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Allocated rooms | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web/css/student-dashboard.css">
	<link rel="stylesheet" href="web/css/admin-dashboard.css">
	<link rel="stylesheet" href="web/css/staff-dashboard.css?v=1">
</head>
<body class="student-dashboard staff-dashboard staff-rooms-page">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero">
		<div>
			<p class="eyebrow">HOSTEL OVERVIEW</p>
			<h1>Allocated rooms</h1>
			<p>View room assignments and resident details for your assigned hostel.</p>
		</div>
		<div class="profile-roll">
			<span>Assigned hostel</span>
			<strong><?php echo h($hostelName); ?></strong>
		</div>
	</section>

	<section class="staff-room-summary" aria-label="Resident overview">
		<div class="admin-stat"><span><?php echo $isSearch ? 'Matching residents' : 'Residents'; ?></span><strong><?php echo count($students); ?></strong></div>
		<div class="admin-stat"><span>Year groups</span><strong><?php echo count($yearBreakdown); ?></strong></div>
		<div class="admin-stat"><span>Departments</span><strong><?php echo count($departmentBreakdown); ?></strong></div>
	</section>

	<div class="staff-breakdown-grid">
		<section class="panel">
			<div class="panel-heading"><div><p class="eyebrow">RESIDENTS</p><h2>By year</h2></div></div>
			<?php if (!$yearBreakdown): ?>
				<p class="empty-state">No student year data is available for this hostel.</p>
			<?php else: ?>
				<ul class="staff-breakdown-list">
					<?php foreach ($yearBreakdown as $year): ?>
						<?php $yearValue = (string)$year['Year_of_study']; ?>
						<li><span><?php echo h(isset($yearNames[$yearValue]) ? $yearNames[$yearValue] . ' year' : 'Year ' . $yearValue); ?></span><strong><?php echo (int)$year['total_students']; ?></strong></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<section class="panel">
			<div class="panel-heading"><div><p class="eyebrow">RESIDENTS</p><h2>By department</h2></div></div>
			<?php if (!$departmentBreakdown): ?>
				<p class="empty-state">No department data is available for this hostel.</p>
			<?php else: ?>
				<ul class="staff-breakdown-list">
					<?php foreach ($departmentBreakdown as $department): ?>
						<li><span><?php echo h($department['Dept'] ?: 'Not specified'); ?></span><strong><?php echo (int)$department['total_students']; ?></strong></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>

	<section class="panel directory-panel">
		<div class="panel-heading">
			<div><p class="eyebrow">ROOM DIRECTORY</p><h2>Resident assignments</h2></div>
		</div>
		<form class="room-search-form" action="allocated_rooms.php" method="post">
			<label for="student-search">Search by student roll number</label>
			<div class="room-search-controls">
				<input id="student-search" type="text" name="search_box" value="<?php echo h($searchTerm); ?>" autocomplete="off">
				<button class="button" type="submit" name="search" value="1">Search</button>
				<?php if ($isSearch): ?><a class="button button-outline" href="allocated_rooms.php">Clear</a><?php endif; ?>
			</div>
		</form>
		<div class="table-responsive">
			<table class="table admin-table">
				<thead><tr><th>Student</th><th>Roll number</th><th>Contact</th><th>Hostel</th><th>Room</th><th>Room occupancy</th></tr></thead>
				<tbody>
					<?php if (!$students): ?>
						<tr><td colspan="6" class="empty-state"><?php echo $isSearch ? 'No matching students were found.' : 'No room allocations are available yet.'; ?></td></tr>
					<?php else: foreach ($students as $student): ?>
						<tr>
							<td><?php echo h(trim($student['Fname'] . ' ' . $student['Lname'])); ?></td>
							<td><?php echo h($student['Student_id']); ?></td>
							<td><?php echo h($student['Mob_no']); ?></td>
							<td><?php echo h($student['Hostel_name']); ?></td>
							<td><?php echo h($student['Room_No'] !== null ? $student['Room_No'] : 'Not assigned'); ?></td>
							<td><?php echo $student['Allocated'] !== null ? (int)$student['Allocated'] . ' / ' . (int)$student['Capacity'] : 'Not assigned'; ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
