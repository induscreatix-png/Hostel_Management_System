<?php
session_start();
require 'includes/config.inc.php';

if (empty($_SESSION['roll'])) {
	header('Location: index.php');
	exit();
}

$messName = trim(isset($_GET['id']) ? $_GET['id'] : '');
$stmt = mysqli_prepare($conn, 'SELECT Mess_id, Mess_name, Mess_type, Vacancy FROM Mess WHERE Mess_name = ?');
mysqli_stmt_bind_param($stmt, 's', $messName);
mysqli_stmt_execute($stmt);
$mess = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$mess) {
	http_response_code(404);
	exit('The requested mess was not found.');
}
$stmt = mysqli_prepare($conn, "SELECT Amount FROM Service_Fee WHERE Service_type = 'mess' AND Service_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $mess['Mess_id']);
mysqli_stmt_execute($stmt);
$messFee = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$feeAvailable = $messFee && $messFee['Amount'] !== null && (int)$messFee['Amount'] > 0;

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
	$studentId = $_SESSION['roll'];

	$stmt = mysqli_prepare($conn, 'SELECT Pwd, Mess_card_id FROM Student WHERE Student_id = ?');
	mysqli_stmt_bind_param($stmt, 's', $studentId);
	mysqli_stmt_execute($stmt);
	$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);
	if (!$student || !password_verify($password, $student['Pwd'])) {
		$error = 'The password you entered is incorrect.';
	} elseif (!$feeAvailable) {
		$error = 'The fee for this mess has not been configured. Please contact the administrator.';
	} elseif (!empty($student['Mess_card_id'])) {
		$error = 'You already have an assigned mess card.';
	} else {
		$stmt = mysqli_prepare($conn, 'SELECT Status, Mess_id FROM Payment WHERE Student_id = ? AND Status = 1 LIMIT 1');
		mysqli_stmt_bind_param($stmt, 's', $studentId);
		mysqli_stmt_execute($stmt);
		$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$payment || (int)$payment['Mess_id'] !== (int)$mess['Mess_id']) {
			$error = 'Please select this mess on the payment screen and get the payment approved before applying.';
		} else {
			$stmt = mysqli_prepare($conn, 'SELECT Application_id FROM Application_mess WHERE Student_id = ? AND Application_status = 1 LIMIT 1');
			mysqli_stmt_bind_param($stmt, 's', $studentId);
			mysqli_stmt_execute($stmt);
			$hasPending = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
			mysqli_stmt_close($stmt);
			if ($hasPending) {
				$error = 'You already have a pending mess application. Check the status on your profile.';
			} elseif ((int)$mess['Vacancy'] < 1) {
				$error = 'This mess currently has no available cards.';
			} else {
				$stmt = mysqli_prepare($conn, 'INSERT INTO Application_mess (Student_id, Mess_id, Application_status, Message) VALUES (?, ?, 1, ?)');
				mysqli_stmt_bind_param($stmt, 'sis', $studentId, $mess['Mess_id'], $message);
				$ok = mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
				if ($ok) {
					$_SESSION['profile_notice'] = 'Your mess application has been submitted. Track its status on your profile.';
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
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Apply for mess <?php echo h($mess['Mess_name']); ?> | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"><link rel="stylesheet" href="web_home/css_home/style.css"><link rel="stylesheet" href="web/css/student-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="panel application-panel">
		<p class="eyebrow">MESS APPLICATION</p><h1>Apply for Mess <?php echo h($mess['Mess_name']); ?></h1>
		<p class="panel-description"><?php echo h($mess['Mess_type']); ?> · <?php echo (int)$mess['Vacancy']; ?> cards available · <?php echo $feeAvailable ? 'Fee: Rs ' . h(number_format((int)$messFee['Amount'])) : 'Fee not configured'; ?></p>
		<?php if ($error !== ''): ?><div class="notice error" role="alert"><?php echo h($error); ?></div><?php endif; ?>
		<form method="post" class="request-form">
			<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
			<label>Student name<input value="<?php echo h($_SESSION['fname'].' '.$_SESSION['lname']); ?>" disabled></label>
			<label>Student ID<input value="<?php echo h($_SESSION['roll']); ?>" disabled></label>
			<label>Account password<input type="password" name="password" required autocomplete="current-password"></label>
			<label>Message (optional)<textarea name="message" rows="4" maxlength="255" placeholder="Add a note for the mess manager"></textarea></label>
			<button class="button" type="submit" <?php echo !$feeAvailable ? 'disabled' : ''; ?>>Submit mess application</button>
		</form>
	</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
