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

if (empty($_SESSION['roll'])) {
	header('Location: index.php');
	exit();
}

$hostelName = trim(isset($_GET['id']) ? $_GET['id'] : '');
$stmt = mysqli_prepare($conn, 'SELECT Hostel_id, Hostel_name FROM Hostel WHERE Hostel_name = ?');
mysqli_stmt_bind_param($stmt, 's', $hostelName);
mysqli_stmt_execute($stmt);
$hostel = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$hostel) {
	http_response_code(404);
	exit('The requested hostel was not found.');
}
$stmt = mysqli_prepare($conn, "SELECT Amount FROM Service_Fee WHERE Service_type = 'hostel' AND Service_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $hostel['Hostel_id']);
mysqli_stmt_execute($stmt);
$hostelFee = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$feeAvailable = $hostelFee && $hostelFee['Amount'] !== null && (int)$hostelFee['Amount'] > 0;
$hasPaymentTable = dbTableExists($conn, 'Payment');

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$password = isset($_POST['password']) ? $_POST['password'] : '';
	$message = trim(isset($_POST['message']) ? $_POST['message'] : '');

	$stmt = mysqli_prepare($conn, 'SELECT Pwd, Hostel_id, Room_id FROM Student WHERE Student_id = ?');
	mysqli_stmt_bind_param($stmt, 's', $_SESSION['roll']);
	mysqli_stmt_execute($stmt);
	$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);
	if (!$student || !password_verify($password, $student['Pwd'])) {
		$error = 'The password you entered is incorrect.';
	} elseif (!empty($student['Room_id'])) {
		$error = 'You already have an allocated room. Request a room change from your profile if needed.';
	} elseif (!$feeAvailable) {
		$error = 'The fee for this hostel has not been configured. Please contact the administrator.';
	} elseif (!$hasPaymentTable) {
		$error = 'The payment service is not configured in the database. Please contact the system administrator.';
	} else {
		$stmt = mysqli_prepare($conn, 'SELECT Status, Hostel_id FROM Payment WHERE Student_id = ? LIMIT 1');
		mysqli_stmt_bind_param($stmt, 's', $_SESSION['roll']);
		mysqli_stmt_execute($stmt);
		$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$payment || (int)$payment['Status'] !== 1 || (int)$payment['Hostel_id'] !== (int)$hostel['Hostel_id']) {
			$error = 'Please select this hostel on the payment screen and get the payment approved before applying.';
		} else {
			$stmt = mysqli_prepare($conn, 'SELECT Application_id FROM Application WHERE Student_id = ? AND Application_status = 1 LIMIT 1');
			mysqli_stmt_bind_param($stmt, 's', $_SESSION['roll']);
			mysqli_stmt_execute($stmt);
			$hasPending = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
			mysqli_stmt_close($stmt);
			if ($hasPending) {
				$error = 'You already have a pending hostel application. Check the status on your profile.';
			} else {
				$stmt = mysqli_prepare($conn, 'INSERT INTO Application (Student_id, Hostel_id, Application_status, Message) VALUES (?, ?, 1, ?)');
				mysqli_stmt_bind_param($stmt, 'sis', $_SESSION['roll'], $hostel['Hostel_id'], $message);
				$ok = mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
				if ($ok) {
					$_SESSION['profile_notice'] = 'Your hostel application has been submitted. You can follow its status on this page.';
					header('Location: profile.php');
					exit();
				}
				$error = 'Unable to submit your application. Please try again.';
			}
		}
	}
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
	<title>Apply for <?php echo h($hostel['Hostel_name']); ?> hostel | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web_home/css_home/style.css">
	<link rel="stylesheet" href="web/css/student-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="panel" style="max-width:680px;margin:0 auto;">
		<p class="eyebrow">HOSTEL APPLICATION</p>
		<h1 style="margin:0 0 8px;font-size:28px;">Apply for <?php echo h($hostel['Hostel_name']); ?> Hostel</h1>
		<p class="panel-description" style="margin-bottom:20px;"><?php echo $feeAvailable ? 'Hostel fee: Rs ' . h(number_format((int)$hostelFee['Amount'])) . '. ' : 'Hostel fee is not configured. '; ?>Enter your password to confirm this application. You can track its status from your profile.</p>
		<?php if ($error !== ''): ?><div class="notice error" role="alert"><?php echo h($error); ?></div><?php endif; ?>
		<?php if (!$hasPaymentTable && $error === ''): ?><div class="notice error" role="alert">Hostel applications require the Payment table. Ask the administrator to import the complete project database schema.</div><?php endif; ?>
		<form method="post" class="request-form">
			<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
			<label>Student name<input value="<?php echo h($_SESSION['fname'].' '.$_SESSION['lname']); ?>" disabled></label>
			<label>Student ID<input value="<?php echo h($_SESSION['roll']); ?>" disabled></label>
			<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
			<label>Message (optional)<textarea name="message" rows="4" maxlength="255" placeholder="Add a note for the hostel manager"></textarea></label>
			<button class="button" type="submit" <?php echo !$hasPaymentTable || !$feeAvailable ? 'disabled' : ''; ?>>Submit application</button>
		</form>
	</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
