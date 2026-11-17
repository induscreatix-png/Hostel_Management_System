<?php
session_start();
require 'includes/config.inc.php';

if (empty($_SESSION['roll'])) {
	header('Location: index.php');
	exit();
}
$tableCheck = mysqli_query($conn, "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'Complaint'");
if (mysqli_num_rows($tableCheck) === 0) {
	http_response_code(503);
	exit('Complaints are not configured. Run database/feature_expansion.sql.');
}
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$notice = isset($_SESSION['complaint_notice']) ? $_SESSION['complaint_notice'] : '';
$noticeType = isset($_SESSION['complaint_notice_type']) ? $_SESSION['complaint_notice_type'] : 'success';
unset($_SESSION['complaint_notice'], $_SESSION['complaint_notice_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$subject = trim(isset($_POST['subject']) ? $_POST['subject'] : '');
	$details = trim(isset($_POST['details']) ? $_POST['details'] : '');
	if ($subject === '' || $details === '') {
		$_SESSION['complaint_notice'] = 'Enter a subject and describe your complaint.';
		$_SESSION['complaint_notice_type'] = 'error';
	} else {
		$stmt = mysqli_prepare($conn, 'SELECT Hostel_id FROM Student WHERE Student_id = ?');
		mysqli_stmt_bind_param($stmt, 's', $_SESSION['roll']);
		mysqli_stmt_execute($stmt);
		$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$student) {
			http_response_code(404);
			exit('Student account not found.');
		}
		if ($student['Hostel_id'] === null) {
			$stmt = mysqli_prepare($conn, "INSERT INTO Complaint (Student_id, Hostel_id, Subject, Details, Status) VALUES (?, NULL, ?, ?, 'Pending')");
			mysqli_stmt_bind_param($stmt, 'sss', $_SESSION['roll'], $subject, $details);
		} else {
			$stmt = mysqli_prepare($conn, "INSERT INTO Complaint (Student_id, Hostel_id, Subject, Details, Status) VALUES (?, ?, ?, ?, 'Pending')");
			mysqli_stmt_bind_param($stmt, 'siss', $_SESSION['roll'], $student['Hostel_id'], $subject, $details);
		}
		$ok = mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		$_SESSION['complaint_notice'] = $ok ? 'Your complaint has been submitted.' : 'Unable to submit your complaint. Please try again.';
		$_SESSION['complaint_notice_type'] = $ok ? 'success' : 'error';
	}
	header('Location: complaints.php');
	exit();
}

$stmt = mysqli_prepare($conn, 'SELECT Complaint_id, Subject, Details, Status, Response, Created_at, Updated_at FROM Complaint WHERE Student_id = ? ORDER BY Created_at DESC, Complaint_id DESC');
mysqli_stmt_bind_param($stmt, 's', $_SESSION['roll']);
mysqli_stmt_execute($stmt);
$complaints = mysqli_stmt_get_result($stmt);
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function complaintStatusClass($status) {
	if ($status === 'Resolved') return 'approved';
	if ($status === 'In progress') return 'pending';
	return 'rejected';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Complaints | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"><link rel="stylesheet" href="web_home/css_home/style.css"><link rel="stylesheet" href="web/css/student-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow">STUDENT SUPPORT</p><h1>Complaints</h1><p>Send a concern to the hostel administration and track its progress.</p></div></section>
	<?php if ($notice !== ''): ?><div class="notice <?php echo h($noticeType); ?>" role="status"><?php echo h($notice); ?></div><?php endif; ?>
	<div class="dashboard-grid">
		<section class="panel"><div class="panel-heading"><div><p class="eyebrow">NEW MESSAGE</p><h2>Submit a complaint</h2></div></div>
			<form method="post" class="request-form">
				<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
				<label>Subject<input name="subject" maxlength="150" required placeholder="Briefly describe the issue"></label>
				<label>Details<textarea name="details" rows="6" required maxlength="5000" placeholder="Include relevant details so the team can help"></textarea></label>
				<button class="button" type="submit">Submit complaint</button>
			</form>
		</section>
		<section class="panel"><div class="panel-heading"><div><p class="eyebrow">YOUR REQUESTS</p><h2>Complaint history</h2></div></div>
			<?php if (mysqli_num_rows($complaints) === 0): ?><p class="empty-state">You haven't submitted any complaints yet.</p><?php endif; ?>
			<?php while ($complaint = mysqli_fetch_assoc($complaints)): ?>
			<article class="complaint-card">
				<div class="status-item"><div><strong><?php echo h($complaint['Subject']); ?></strong><p>Submitted <?php echo h($complaint['Created_at']); ?></p></div><span class="status-badge <?php echo h(complaintStatusClass($complaint['Status'])); ?>"><?php echo h($complaint['Status']); ?></span></div>
				<div class="complaint-details"><?php echo h($complaint['Details']); ?></div>
				<?php if (!empty($complaint['Response'])): ?><div class="complaint-response"><strong>Administration response</strong><br><?php echo h($complaint['Response']); ?></div><?php endif; ?>
			</article>
			<?php endwhile; ?>
		</section>
	</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
