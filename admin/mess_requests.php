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
$notice = isset($_SESSION['mess_request_notice']) ? $_SESSION['mess_request_notice'] : '';
unset($_SESSION['mess_request_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
	$decision = isset($_POST['decision']) ? $_POST['decision'] : '';
	if (!$requestId || !in_array($decision, array('approve', 'reject'), true)) {
		http_response_code(400);
		exit('Invalid mess request.');
	}

	mysqli_begin_transaction($conn);
	$stmt = mysqli_prepare($conn, 'SELECT a.Student_id, a.Mess_id, s.Mess_card_id FROM Application_mess a JOIN Student s ON s.Student_id = a.Student_id WHERE a.Application_id = ? AND a.Application_status = 1 FOR UPDATE');
	mysqli_stmt_bind_param($stmt, 'i', $requestId);
	mysqli_stmt_execute($stmt);
	$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);
	if (!$request) {
		mysqli_rollback($conn);
		$_SESSION['mess_request_notice'] = 'That mess request is no longer pending.';
	} elseif ($decision === 'reject') {
		$stmt = mysqli_prepare($conn, 'UPDATE Application_mess SET Application_status = -1 WHERE Application_id = ? AND Application_status = 1');
		mysqli_stmt_bind_param($stmt, 'i', $requestId);
		mysqli_stmt_execute($stmt);
		$updated = mysqli_stmt_affected_rows($stmt) === 1;
		mysqli_stmt_close($stmt);
		if ($updated) {
			mysqli_commit($conn);
			$_SESSION['mess_request_notice'] = 'Mess request rejected.';
		} else {
			mysqli_rollback($conn);
			$_SESSION['mess_request_notice'] = 'The mess request changed before it could be rejected.';
		}
	} elseif (!empty($request['Mess_card_id'])) {
		mysqli_rollback($conn);
		$_SESSION['mess_request_notice'] = 'The student already has an assigned mess card.';
	} else {
		$messId = (int)$request['Mess_id'];
		$stmt = mysqli_prepare($conn, 'SELECT Mess_card_id, Mess_card_No FROM Mess_Allocation WHERE Mess_id = ? AND Allocated = 0 ORDER BY Mess_card_No LIMIT 1 FOR UPDATE');
		mysqli_stmt_bind_param($stmt, 'i', $messId);
		mysqli_stmt_execute($stmt);
		$card = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$card) {
			mysqli_rollback($conn);
			$_SESSION['mess_request_notice'] = 'No mess cards are available for this application.';
		} else {
			$cardId = (int)$card['Mess_card_id'];
			$cardNumber = (int)$card['Mess_card_No'];
			$stmt = mysqli_prepare($conn, 'UPDATE Mess_Allocation SET Allocated = 1 WHERE Mess_card_id = ? AND Allocated = 0');
			mysqli_stmt_bind_param($stmt, 'i', $cardId);
			mysqli_stmt_execute($stmt);
			$cardUpdated = mysqli_stmt_affected_rows($stmt) === 1;
			mysqli_stmt_close($stmt);
			if (!$cardUpdated) {
				mysqli_rollback($conn);
				$_SESSION['mess_request_notice'] = 'The selected mess card is no longer available.';
			} else {
				$stmt = mysqli_prepare($conn, 'UPDATE Student SET Mess_id = ?, Mess_card_id = ? WHERE Student_id = ? AND Mess_card_id IS NULL');
				mysqli_stmt_bind_param($stmt, 'iis', $messId, $cardId, $request['Student_id']);
				mysqli_stmt_execute($stmt);
				$studentUpdated = mysqli_stmt_affected_rows($stmt) === 1;
				mysqli_stmt_close($stmt);
				if (!$studentUpdated) {
					mysqli_rollback($conn);
					$_SESSION['mess_request_notice'] = 'The student allocation changed. No changes were saved.';
				} else {
					$stmt = mysqli_prepare($conn, 'UPDATE Application_mess SET Application_status = 0, Mess_card_No = ? WHERE Application_id = ? AND Application_status = 1');
					mysqli_stmt_bind_param($stmt, 'ii', $cardNumber, $requestId);
					mysqli_stmt_execute($stmt);
					$applicationUpdated = mysqli_stmt_affected_rows($stmt) === 1;
					mysqli_stmt_close($stmt);
					if (!$applicationUpdated) {
						mysqli_rollback($conn);
						$_SESSION['mess_request_notice'] = 'The request changed before card allocation could be completed.';
					} else {
						mysqli_commit($conn);
						$_SESSION['mess_request_notice'] = 'Mess request approved and card allocated.';
					}
				}
			}
		}
	}
	header('Location: mess_requests.php');
	exit();
}

$requests = mysqli_query($conn, 'SELECT a.Application_id, a.Student_id, a.Message, a.Mess_id, m.Mess_name, (SELECT COUNT(*) FROM Mess_Allocation ma WHERE ma.Mess_id = a.Mess_id AND ma.Allocated = 0) AS Available_cards, s.Fname, s.Lname, s.Dept, s.Mob_no FROM Application_mess a JOIN Student s ON s.Student_id = a.Student_id JOIN Mess m ON m.Mess_id = a.Mess_id WHERE a.Application_status = 1 ORDER BY a.Application_id');
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Mess applications | Nayab Hostel</title>
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"><link rel="stylesheet" href="../web_home/css_home/style.css"><link rel="stylesheet" href="../web/css/student-dashboard.css"><link rel="stylesheet" href="../web/css/admin-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow">ADMINISTRATION</p><h1>Mess applications</h1><p>Accept a request to allocate the next available mess card, or reject it.</p></div></section>
	<?php if ($notice !== ''): ?><div class="notice success" role="status"><?php echo h($notice); ?></div><?php endif; ?>
	<section class="panel directory-panel"><div class="panel-heading"><div><p class="eyebrow">PENDING APPLICATIONS</p><h2>Student requests</h2></div></div>
		<div class="table-responsive"><table class="table admin-table"><thead><tr><th>Student</th><th>Requested mess</th><th>Availability</th><th>Message</th><th>Decision</th></tr></thead><tbody>
		<?php if (!$requests || mysqli_num_rows($requests) === 0): ?><tr><td colspan="5" class="empty-state">No pending mess applications.</td></tr><?php endif; ?>
		<?php if ($requests): while ($request = mysqli_fetch_assoc($requests)): ?><tr>
			<td><?php echo h($request['Fname'].' '.$request['Lname']); ?><br><small><?php echo h($request['Student_id']); ?> · <?php echo h($request['Dept']); ?> · <?php echo h($request['Mob_no']); ?></small></td>
			<td>Mess <?php echo h($request['Mess_name']); ?></td>
			<td><?php echo (int)$request['Available_cards']; ?> cards available</td>
			<td><?php echo h($request['Message'] ?: 'No additional message.'); ?></td>
			<td><form method="post" class="decision-form">
				<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>"><input type="hidden" name="request_id" value="<?php echo (int)$request['Application_id']; ?>">
				<button class="button" type="submit" name="decision" value="approve" <?php echo (int)$request['Available_cards'] < 1 ? 'disabled' : ''; ?>>Accept and allocate</button><button class="button button-outline" type="submit" name="decision" value="reject">Reject</button>
			</form></td>
		</tr><?php endwhile; endif; ?>
		</tbody></table></div>
	</section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
