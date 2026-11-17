<?php
session_start();
require 'includes/config.inc.php';
require 'includes/manager_access.php';

function dbTableExists($conn, $table) {
	$stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
	mysqli_stmt_bind_param($stmt, 's', $table);
	mysqli_stmt_execute($stmt);
	$exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
	mysqli_stmt_close($stmt);
	return $exists;
}

function dbColumnExists($conn, $table, $column) {
	$stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
	mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
	mysqli_stmt_execute($stmt);
	$exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
	mysqli_stmt_close($stmt);
	return $exists;
}

$manager = requireManagerAccess($conn, array('Warden', 'Hostel Manager', 'Mess Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');
$isHostelStaff = in_array($manager['Position'], array('Warden', 'Hostel Manager'), true);
$isMessStaff = $manager['Position'] === 'Mess Manager';
$hostelId = (int)$manager['Hostel_id'];
$messId = (int)$manager['Mess_id'];
$hasMessCard = dbColumnExists($conn, 'Student', 'Mess_card_id') && dbTableExists($conn, 'Mess_Allocation');
$messCardField = $hasMessCard ? 's.Mess_card_id' : 'NULL AS Mess_card_id';
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$notice = isset($_SESSION['request_notice']) ? $_SESSION['request_notice'] : '';
unset($_SESSION['request_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$type = isset($_POST['request_type']) ? $_POST['request_type'] : '';
	$requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
	$decision = isset($_POST['decision']) ? $_POST['decision'] : '';
	if (!$requestId || !in_array($type, array('room_change', 'passout', 'mess'), true) || !in_array($decision, array('approve', 'reject'), true)) {
		http_response_code(400);
		exit('Invalid request.');
	}
	if (($type === 'mess' && !$isMessStaff) || ($type !== 'mess' && !$isHostelStaff)) {
		http_response_code(403);
		exit('Your staff role cannot review this request type.');
	}

	if ($type === 'mess') {
		if ($messId < 1) {
			http_response_code(403);
			exit('No mess is assigned to this manager account.');
		}
		mysqli_begin_transaction($conn);
		$stmt = mysqli_prepare($conn, 'SELECT a.Student_id, s.Mess_card_id FROM Application_mess a JOIN Student s ON s.Student_id = a.Student_id WHERE a.Application_id = ? AND a.Application_status = 1 AND a.Mess_id = ? FOR UPDATE');
		mysqli_stmt_bind_param($stmt, 'ii', $requestId, $messId);
		mysqli_stmt_execute($stmt);
		$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$request) {
			mysqli_rollback($conn);
			$_SESSION['request_notice'] = 'That mess request is no longer pending or is not for your assigned mess.';
		} elseif ($decision === 'reject') {
			$stmt = mysqli_prepare($conn, 'UPDATE Application_mess SET Application_status = -1 WHERE Application_id = ? AND Application_status = 1');
			mysqli_stmt_bind_param($stmt, 'i', $requestId);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			mysqli_commit($conn);
			$_SESSION['request_notice'] = 'Mess request rejected.';
		} elseif (!empty($request['Mess_card_id'])) {
			mysqli_rollback($conn);
			$_SESSION['request_notice'] = 'The student already has an assigned mess card.';
		} else {
			$stmt = mysqli_prepare($conn, 'SELECT Mess_card_id, Mess_card_No FROM Mess_Allocation WHERE Mess_id = ? AND Allocated = 0 ORDER BY Mess_card_No LIMIT 1 FOR UPDATE');
			mysqli_stmt_bind_param($stmt, 'i', $messId);
			mysqli_stmt_execute($stmt);
			$card = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
			mysqli_stmt_close($stmt);
			if (!$card) {
				mysqli_rollback($conn);
				$_SESSION['request_notice'] = 'No mess cards are currently available.';
			} else {
				$cardId = (int)$card['Mess_card_id'];
				$cardNumber = (int)$card['Mess_card_No'];
				$stmt = mysqli_prepare($conn, 'UPDATE Mess_Allocation SET Allocated = 1 WHERE Mess_card_id = ? AND Allocated = 0');
				mysqli_stmt_bind_param($stmt, 'i', $cardId);
				mysqli_stmt_execute($stmt);
				$cardAllocated = mysqli_stmt_affected_rows($stmt) === 1;
				mysqli_stmt_close($stmt);
				if (!$cardAllocated) {
					mysqli_rollback($conn);
					$_SESSION['request_notice'] = 'The selected mess card is no longer available.';
				} else {
					$stmt = mysqli_prepare($conn, 'UPDATE Student SET Mess_id = ?, Mess_card_id = ? WHERE Student_id = ? AND Mess_card_id IS NULL');
					mysqli_stmt_bind_param($stmt, 'iis', $messId, $cardId, $request['Student_id']);
					mysqli_stmt_execute($stmt);
					$studentUpdated = mysqli_stmt_affected_rows($stmt) === 1;
					mysqli_stmt_close($stmt);
					if (!$studentUpdated) {
						mysqli_rollback($conn);
						$_SESSION['request_notice'] = 'The student allocation changed. No changes were saved.';
					} else {
						$stmt = mysqli_prepare($conn, 'UPDATE Application_mess SET Application_status = 0, Mess_card_No = ? WHERE Application_id = ? AND Application_status = 1');
						mysqli_stmt_bind_param($stmt, 'ii', $cardNumber, $requestId);
						mysqli_stmt_execute($stmt);
						mysqli_stmt_close($stmt);
						mysqli_commit($conn);
						$_SESSION['request_notice'] = 'Mess request approved and card allocated.';
					}
				}
			}
		}
		header('Location: manage_student_requests.php');
		exit();
	}

	mysqli_begin_transaction($conn);
	if ($type === 'room_change') {
		$requestSql = 'SELECT a.Student_id, a.Current_room_id, a.Requested_hostel_id, s.Room_id AS Student_room_id, s.Hostel_id AS Student_hostel_id FROM Room_Change_Application a JOIN Student s ON s.Student_id = a.Student_id WHERE a.Application_id = ? AND a.Status = 1';
		$requestSql .= ' AND a.Requested_hostel_id = ?';
		$requestSql .= ' FOR UPDATE';
		$stmt = mysqli_prepare($conn, $requestSql);
		mysqli_stmt_bind_param($stmt, 'ii', $requestId, $hostelId);
		mysqli_stmt_execute($stmt);
		$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$request) {
			mysqli_rollback($conn);
			$_SESSION['request_notice'] = 'That room-change request is no longer pending or is not for your hostel.';
		} elseif ($decision === 'approve' && (int)$request['Student_room_id'] !== (int)$request['Current_room_id']) {
			mysqli_rollback($conn);
			$_SESSION['request_notice'] = 'The student allocation changed after the request was submitted. The request was not approved.';
		} elseif ($decision === 'approve') {
			$reviewHostelId = (int)$request['Requested_hostel_id'];
			$stmt = mysqli_prepare($conn, "SELECT Room_id FROM Room WHERE Hostel_id = ? AND Allocated < Capacity AND Room_id <> ? ORDER BY Room_No LIMIT 1 FOR UPDATE");
			mysqli_stmt_bind_param($stmt, 'ii', $reviewHostelId, $request['Current_room_id']);
			mysqli_stmt_execute($stmt);
			$room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
			mysqli_stmt_close($stmt);
			if (!$room) {
				mysqli_rollback($conn);
				$_SESSION['request_notice'] = 'No rooms are currently available in your hostel.';
			} else {
				$stmt = mysqli_prepare($conn, "UPDATE Room SET Allocated = Allocated + 1 WHERE Room_id = ? AND Allocated < Capacity");
				mysqli_stmt_bind_param($stmt, 'i', $room['Room_id']);
				mysqli_stmt_execute($stmt);
				$allocated = mysqli_stmt_affected_rows($stmt) === 1;
				mysqli_stmt_close($stmt);
				if (!$allocated) {
					mysqli_rollback($conn);
					$_SESSION['request_notice'] = 'The selected room is no longer available. Please retry.';
				} else {
					$stmt = mysqli_prepare($conn, 'UPDATE Room SET Allocated = Allocated - 1 WHERE Room_id = ? AND Allocated > 0');
					mysqli_stmt_bind_param($stmt, 'i', $request['Current_room_id']);
					mysqli_stmt_execute($stmt);
					$oldRoomReleased = mysqli_stmt_affected_rows($stmt) === 1;
					mysqli_stmt_close($stmt);
					if (!$oldRoomReleased) {
						mysqli_rollback($conn);
						$_SESSION['request_notice'] = 'The current room allocation changed. No changes were saved.';
					} else {
						$stmt = mysqli_prepare($conn, 'UPDATE Student SET Hostel_id = ?, Room_id = ? WHERE Student_id = ? AND Room_id = ?');
						mysqli_stmt_bind_param($stmt, 'iisi', $reviewHostelId, $room['Room_id'], $request['Student_id'], $request['Current_room_id']);
						mysqli_stmt_execute($stmt);
						$updated = mysqli_stmt_affected_rows($stmt) === 1;
						mysqli_stmt_close($stmt);
						if (!$updated) {
							mysqli_rollback($conn);
							$_SESSION['request_notice'] = 'The student allocation changed. No changes were saved.';
						} else {
							$stmt = mysqli_prepare($conn, 'UPDATE Room_Change_Application SET Status = 0, Reviewed_at = CURRENT_TIMESTAMP WHERE Application_id = ? AND Status = 1');
							mysqli_stmt_bind_param($stmt, 'i', $requestId);
							mysqli_stmt_execute($stmt);
							mysqli_stmt_close($stmt);
							mysqli_commit($conn);
							$_SESSION['request_notice'] = 'Room-change request approved and room allocated.';
						}
					}
				}
			}
		} else {
			$stmt = mysqli_prepare($conn, 'UPDATE Room_Change_Application SET Status = -1, Reviewed_at = CURRENT_TIMESTAMP WHERE Application_id = ? AND Status = 1');
			mysqli_stmt_bind_param($stmt, 'i', $requestId);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			mysqli_commit($conn);
			$_SESSION['request_notice'] = 'Room-change request rejected.';
		}
	} else {
		$requestSql = 'SELECT a.Student_id, s.Hostel_id, s.Room_id, ' . $messCardField . ' FROM Passout_Application a JOIN Student s ON s.Student_id = a.Student_id WHERE a.Application_id = ? AND a.Status = 1';
		$requestSql .= ' AND s.Hostel_id = ?';
		$requestSql .= ' FOR UPDATE';
		$stmt = mysqli_prepare($conn, $requestSql);
		mysqli_stmt_bind_param($stmt, 'ii', $requestId, $hostelId);
		mysqli_stmt_execute($stmt);
		$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$request) {
			mysqli_rollback($conn);
			$_SESSION['request_notice'] = 'That pass-out request is no longer pending or is not assigned to your hostel.';
		} elseif ($decision === 'approve') {
			$reviewHostelId = (int)$request['Hostel_id'];
			if (!empty($request['Room_id'])) {
				$stmt = mysqli_prepare($conn, 'UPDATE Room SET Allocated = Allocated - 1 WHERE Room_id = ? AND Hostel_id = ? AND Allocated > 0');
				mysqli_stmt_bind_param($stmt, 'ii', $request['Room_id'], $reviewHostelId);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
			if ($hasMessCard && !empty($request['Mess_card_id'])) {
				$cardStmt = mysqli_prepare($conn, 'SELECT Mess_id FROM Mess_Allocation WHERE Mess_card_id = ?');
				mysqli_stmt_bind_param($cardStmt, 'i', $request['Mess_card_id']);
				mysqli_stmt_execute($cardStmt);
				$cardInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($cardStmt));
				mysqli_stmt_close($cardStmt);
				$stmt = mysqli_prepare($conn, 'UPDATE Mess_Allocation SET Allocated = 0 WHERE Mess_card_id = ?');
				mysqli_stmt_bind_param($stmt, 'i', $request['Mess_card_id']);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
				if ($cardInfo) {
					$messId = (int)$cardInfo['Mess_id'];
					$recount = mysqli_prepare($conn, 'UPDATE Mess SET Vacancy = GREATEST(0, Size - (SELECT COUNT(*) FROM Mess_Allocation WHERE Mess_id = ? AND Allocated = 1)) WHERE Mess_id = ?');
					mysqli_stmt_bind_param($recount, 'ii', $messId, $messId);
					mysqli_stmt_execute($recount);
					mysqli_stmt_close($recount);
				}
			}
			$releaseFields = 'Hostel_id = NULL, Room_id = NULL';
			if (dbColumnExists($conn, 'Student', 'Mess_id')) {
				$releaseFields .= ', Mess_id = NULL';
			}
			if (dbColumnExists($conn, 'Student', 'Mess_card_id')) {
				$releaseFields .= ', Mess_card_id = NULL';
			}
			$stmt = mysqli_prepare($conn, 'UPDATE Student SET ' . $releaseFields . ' WHERE Student_id = ? AND Hostel_id = ?');
			mysqli_stmt_bind_param($stmt, 'si', $request['Student_id'], $reviewHostelId);
			mysqli_stmt_execute($stmt);
			$updated = mysqli_stmt_affected_rows($stmt) === 1;
			mysqli_stmt_close($stmt);
			if (!$updated) {
				mysqli_rollback($conn);
				$_SESSION['request_notice'] = 'The student allocation changed. No changes were saved.';
			} else {
				$stmt = mysqli_prepare($conn, 'UPDATE Passout_Application SET Status = 0, Reviewed_at = CURRENT_TIMESTAMP WHERE Application_id = ? AND Status = 1');
				mysqli_stmt_bind_param($stmt, 'i', $requestId);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
				$stmt = mysqli_prepare($conn, 'UPDATE Room_Change_Application SET Status = -1, Reviewed_at = CURRENT_TIMESTAMP WHERE Student_id = ? AND Status = 1');
				mysqli_stmt_bind_param($stmt, 's', $request['Student_id']);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
				mysqli_commit($conn);
				$_SESSION['request_notice'] = 'Pass-out request approved. The student room and mess allocations were released.';
			}
		} else {
			$stmt = mysqli_prepare($conn, 'UPDATE Passout_Application SET Status = -1, Reviewed_at = CURRENT_TIMESTAMP WHERE Application_id = ? AND Status = 1');
			mysqli_stmt_bind_param($stmt, 'i', $requestId);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			mysqli_commit($conn);
			$_SESSION['request_notice'] = 'Pass-out request rejected.';
		}
	}
	header('Location: manage_student_requests.php');
	exit();
}

$roomRequests = false;
$passoutRequests = false;
$messRequests = false;
if ($isHostelStaff) {
	$roomRequestSql = 'SELECT a.Application_id, a.Student_id, a.Message, a.Created_at, s.Fname, s.Lname, s.Mob_no, ch.Hostel_name AS Current_hostel, cr.Room_No AS Current_room, th.Hostel_name AS Requested_hostel FROM Room_Change_Application a JOIN Student s ON s.Student_id = a.Student_id LEFT JOIN Hostel ch ON ch.Hostel_id = s.Hostel_id LEFT JOIN Room cr ON cr.Room_id = a.Current_room_id JOIN Hostel th ON th.Hostel_id = a.Requested_hostel_id WHERE a.Status = 1 AND a.Requested_hostel_id = ? ORDER BY a.Created_at';
	$stmt = mysqli_prepare($conn, $roomRequestSql);
	mysqli_stmt_bind_param($stmt, 'i', $hostelId);
	mysqli_stmt_execute($stmt);
	$roomRequests = mysqli_stmt_get_result($stmt);
	$passoutRequestSql = 'SELECT a.Application_id, a.Student_id, a.Message, a.Created_at, s.Fname, s.Lname, s.Mob_no, h.Hostel_name, r.Room_No FROM Passout_Application a JOIN Student s ON s.Student_id = a.Student_id LEFT JOIN Hostel h ON h.Hostel_id = s.Hostel_id LEFT JOIN Room r ON r.Room_id = s.Room_id WHERE a.Status = 1 AND s.Hostel_id = ? ORDER BY a.Created_at';
	$stmt = mysqli_prepare($conn, $passoutRequestSql);
	mysqli_stmt_bind_param($stmt, 'i', $hostelId);
	mysqli_stmt_execute($stmt);
	$passoutRequests = mysqli_stmt_get_result($stmt);
} else {
	$messRequests = mysqli_query($conn, 'SELECT a.Application_id, a.Student_id, a.Message, s.Fname, s.Lname, s.Mob_no, m.Mess_name FROM Application_mess a JOIN Student s ON s.Student_id = a.Student_id JOIN Mess m ON m.Mess_id = a.Mess_id WHERE a.Application_status = 1 AND a.Mess_id = ' . $messId . ' ORDER BY a.Application_id');
}

function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function requestActions($type, $id) {
	?>
	<form class="decision-form" method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
		<input type="hidden" name="request_type" value="<?php echo h($type); ?>">
		<input type="hidden" name="request_id" value="<?php echo (int)$id; ?>">
		<button class="button" type="submit" name="decision" value="approve">Accept</button>
		<button class="button button-outline" type="submit" name="decision" value="reject">Reject</button>
	</form>
	<?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Student requests | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"><link rel="stylesheet" href="web_home/css_home/style.css"><link rel="stylesheet" href="web/css/student-dashboard.css">
	<style>
		.request-card { padding:18px 0; border-top:1px solid #edf1f6; }
		.request-card:first-child { border-top:0; }
		.request-meta { color:#6b7890; font-size:13px; line-height:1.7; }
		.request-card p { margin:8px 0 14px; color:#536178; }
		.decision-form { display:flex; flex-wrap:wrap; gap:9px; }
	</style>
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow"><?php echo h($manager['Position']); ?></p><h1>Student requests</h1><p><?php echo $isHostelStaff ? 'Review room-change and pass-out requests for your assigned hostel.' : 'Review mess applications for your assigned mess.'; ?></p></div></section>
	<?php if ($notice !== ''): ?><div class="notice success" role="status"><?php echo h($notice); ?></div><?php endif; ?>
	<div class="dashboard-grid">
		<?php if ($isHostelStaff): ?><section class="panel"><div class="panel-heading"><div><p class="eyebrow">ROOM CHANGE</p><h2>Pending requests</h2></div></div>
			<?php if (mysqli_num_rows($roomRequests) === 0): ?><p class="empty-state">No pending room-change requests.</p><?php endif; ?>
			<?php while ($request = mysqli_fetch_assoc($roomRequests)): ?><article class="request-card"><strong><?php echo h($request['Fname'].' '.$request['Lname']); ?></strong><div class="request-meta"><?php echo h($request['Student_id']); ?> · <?php echo h($request['Mob_no']); ?><br>Current: <?php echo h($request['Current_hostel'] ?: 'Unknown'); ?>, room <?php echo h($request['Current_room'] ?: 'Unknown'); ?> · Requested: <?php echo h($request['Requested_hostel']); ?><br>Submitted <?php echo h($request['Created_at']); ?></div><p><?php echo h($request['Message'] ?: 'No additional message.'); ?></p><?php requestActions('room_change', $request['Application_id']); ?></article><?php endwhile; ?>
		</section>
		<section class="panel"><div class="panel-heading"><div><p class="eyebrow">PASS-OUT</p><h2>Pending requests</h2></div></div>
			<?php if (mysqli_num_rows($passoutRequests) === 0): ?><p class="empty-state">No pending pass-out requests.</p><?php endif; ?>
			<?php while ($request = mysqli_fetch_assoc($passoutRequests)): ?><article class="request-card"><strong><?php echo h($request['Fname'].' '.$request['Lname']); ?></strong><div class="request-meta"><?php echo h($request['Student_id']); ?> · <?php echo h($request['Mob_no']); ?><br><?php echo h($request['Hostel_name']); ?>, room <?php echo h($request['Room_No'] ?: 'Unknown'); ?><br>Submitted <?php echo h($request['Created_at']); ?></div><p><?php echo h($request['Message'] ?: 'No additional message.'); ?></p><?php requestActions('passout', $request['Application_id']); ?></article><?php endwhile; ?>
		</section><?php endif; ?>
		<?php if ($isMessStaff): ?><section class="panel"><div class="panel-heading"><div><p class="eyebrow">MESS</p><h2>Pending applications</h2></div></div>
			<?php if (!$messRequests || mysqli_num_rows($messRequests) === 0): ?><p class="empty-state">No pending mess applications for your assigned mess.</p><?php endif; ?>
			<?php if ($messRequests): while ($request = mysqli_fetch_assoc($messRequests)): ?><article class="request-card"><strong><?php echo h($request['Fname'].' '.$request['Lname']); ?></strong><div class="request-meta"><?php echo h($request['Student_id']); ?> · <?php echo h($request['Mob_no']); ?><br>Mess <?php echo h($request['Mess_name']); ?></div><p><?php echo h($request['Message'] ?: 'No additional message.'); ?></p><?php requestActions('mess', $request['Application_id']); ?></article><?php endwhile; endif; ?>
		</section><?php endif; ?>
	</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
