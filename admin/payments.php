<?php
session_start();
require '../includes/config.inc.php';

$isAdmin = isset($_SESSION['isadmin']) && (int)$_SESSION['isadmin'] === 1;
if (!$isAdmin) {
	require '../includes/manager_access.php';
	requireManagerAccess($conn, array('Finance Reviewer'), '../login-hostel_manager.php', 'admin_home.php');
}
if (empty($_SESSION['hostel_man_id']) || (!$isAdmin && (!isset($_SESSION['position']) || $_SESSION['position'] !== 'Finance Reviewer'))) {
	header('Location: ../login-hostel_manager.php');
	exit();
}
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$notice = '';
$noticeType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$action = isset($_POST['action']) ? $_POST['action'] : 'review_payment';
	if ($action === 'save_fee') {
		if (!$isAdmin) {
			http_response_code(403);
			exit('Only administrators can change fees.');
		}
		$feeType = isset($_POST['fee_type']) ? $_POST['fee_type'] : '';
		$feeId = filter_input(INPUT_POST, 'fee_id', FILTER_VALIDATE_INT);
		$amountText = trim(isset($_POST['amount']) ? $_POST['amount'] : '');
		if (!in_array($feeType, array('hostel', 'mess'), true) || !$feeId || ($amountText !== '' && (!ctype_digit($amountText) || (int)$amountText > 1000000000))) {
			http_response_code(400);
			exit('Invalid fee configuration.');
		}
		$table = $feeType === 'hostel' ? 'Hostel' : 'Mess';
		$nameColumn = $feeType === 'hostel' ? 'Hostel_name' : 'Mess_name';
		$idColumn = $feeType === 'hostel' ? 'Hostel_id' : 'Mess_id';
		$stmt = mysqli_prepare($conn, 'SELECT ' . $nameColumn . ' AS Service_name FROM ' . $table . ' WHERE ' . $idColumn . ' = ?');
		mysqli_stmt_bind_param($stmt, 'i', $feeId);
		mysqli_stmt_execute($stmt);
		$service = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		mysqli_stmt_close($stmt);
		if (!$service) {
			http_response_code(400);
			exit('The selected hostel or mess does not exist.');
		} elseif (in_array($service['Service_name'], array('A', 'G'), true)) {
			$notice = 'Hostel and mess A/G rates are fixed to the supplied amounts.';
			$noticeType = 'error';
		} else {
			if ($amountText === '' || (int)$amountText === 0) {
				$stmt = mysqli_prepare($conn, 'INSERT INTO Service_Fee (Service_type, Service_id, Amount) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE Amount = NULL');
				mysqli_stmt_bind_param($stmt, 'si', $feeType, $feeId);
			} else {
				$amountValue = (int)$amountText;
				$stmt = mysqli_prepare($conn, 'INSERT INTO Service_Fee (Service_type, Service_id, Amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Amount = VALUES(Amount)');
				mysqli_stmt_bind_param($stmt, 'sii', $feeType, $feeId, $amountValue);
			}
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			$notice = $ok ? 'Service fee saved.' : 'The fee could not be saved.';
			$noticeType = $ok ? 'success' : 'error';
		}
	} else {
		$studentId = trim(isset($_POST['student_id']) ? $_POST['student_id'] : '');
		$decision = isset($_POST['decision']) ? $_POST['decision'] : '';
		$reviewNote = trim(isset($_POST['review_note']) ? $_POST['review_note'] : '');
		if ($studentId === '' || !in_array($decision, array('approve', 'reject'), true) || strlen($reviewNote) > 255) {
			http_response_code(400);
			exit('Invalid payment review request.');
		}
		$status = $decision === 'approve' ? 1 : -1;
		$stmt = mysqli_prepare($conn, 'UPDATE Payment SET Status = ?, Reviewed_at = CURRENT_TIMESTAMP, Review_note = ? WHERE Student_id = ? AND Status = 2');
		mysqli_stmt_bind_param($stmt, 'iss', $status, $reviewNote, $studentId);
		$ok = mysqli_stmt_execute($stmt);
		$changed = mysqli_stmt_affected_rows($stmt);
		mysqli_stmt_close($stmt);
		if ($ok && $changed === 1) {
			$notice = $decision === 'approve' ? 'Payment approved.' : 'Payment rejected.';
		} else {
			$notice = 'The payment is no longer pending or could not be updated.';
			$noticeType = 'error';
		}
	}
}

$paymentStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(CASE WHEN Status = 1 AND Proof_path IS NOT NULL AND Proof_path <> '' THEN 1 END) AS approved_count, COUNT(CASE WHEN Status = 2 THEN 1 END) AS pending_count, COALESCE(SUM(CASE WHEN Status = 1 AND Proof_path IS NOT NULL AND Proof_path <> '' THEN Amount ELSE 0 END), 0) AS amount_paid FROM Payment"));
$payments = mysqli_query($conn, 'SELECT s.Student_id, s.Fname, s.Lname, s.Dept, h.Hostel_name, m.Mess_name, p.Status, p.Amount, p.Transaction_id, p.Proof_path, p.Submitted_at, p.Review_note FROM Payment p JOIN Student s ON s.Student_id = p.Student_id LEFT JOIN Hostel h ON h.Hostel_id = p.Hostel_id LEFT JOIN Mess m ON m.Mess_id = p.Mess_id ORDER BY p.Status DESC, p.Submitted_at DESC, s.Student_id');
$hostelFees = mysqli_query($conn, "SELECT h.Hostel_id AS Service_id, h.Hostel_name AS Service_name, f.Amount FROM Hostel h LEFT JOIN Service_Fee f ON f.Service_type = 'hostel' AND f.Service_id = h.Hostel_id ORDER BY h.Hostel_name");
$messFees = mysqli_query($conn, "SELECT m.Mess_id AS Service_id, m.Mess_name AS Service_name, f.Amount FROM Mess m LEFT JOIN Service_Fee f ON f.Service_type = 'mess' AND f.Service_id = m.Mess_id ORDER BY m.Mess_name");
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function paymentLabel($status, $proofPath) {
	if ((int)$status === 1 && empty($proofPath)) return 'Needs proof';
	if ((int)$status === 1) return 'Approved';
	if ((int)$status === 2) return 'Pending';
	if ((int)$status === -1) return 'Rejected';
	return 'Unpaid';
}
function paymentClass($status) {
	if ((int)$status === 1) return 'approved';
	if ((int)$status === 2) return 'pending';
	if ((int)$status === -1) return 'rejected';
	return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Payments | Nayab Hostel</title>
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"><link rel="stylesheet" href="../web_home/css_home/style.css"><link rel="stylesheet" href="../web/css/student-dashboard.css"><link rel="stylesheet" href="../web/css/admin-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow">FINANCE</p><h1>Payment review</h1><p>Check each submitted transaction screenshot and accept or reject pending payments.</p></div></section>
	<?php if ($notice !== ''): ?><div class="notice <?php echo h($noticeType); ?>" role="status"><?php echo h($notice); ?></div><?php endif; ?>
	<div class="admin-stats">
		<div class="admin-stat"><span>Approved payments</span><strong><?php echo (int)$paymentStats['approved_count']; ?></strong></div>
		<div class="admin-stat"><span>Awaiting review</span><strong><?php echo (int)$paymentStats['pending_count']; ?></strong></div>
		<div class="admin-stat"><span>Total approved amount</span><strong>Rs <?php echo h(number_format((float)$paymentStats['amount_paid'])); ?></strong></div>
	</div>
	<?php if ($isAdmin): ?>
	<section class="panel directory-panel">
		<div class="panel-heading"><div><p class="eyebrow">FEE CONFIGURATION</p><h2>Hostel and mess rates</h2></div></div>
		<p class="panel-description">The A/G rates are fixed to the supplied amounts. Enter a rate for another option to make it available for payment; leave it blank to disable it.</p>
		<div class="dashboard-grid">
			<div><h3>Hostels</h3><div class="table-responsive"><table class="table admin-table"><thead><tr><th>Hostel</th><th>Rate (Rs)</th><th></th></tr></thead><tbody>
				<?php while ($fee = mysqli_fetch_assoc($hostelFees)): ?><tr><td>Hostel <?php echo h($fee['Service_name']); ?></td><td>
					<?php if (in_array($fee['Service_name'], array('A', 'G'), true)): ?>Rs <?php echo h(number_format((int)$fee['Amount'])); ?><?php else: ?>
					<form method="post"><input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="save_fee"><input type="hidden" name="fee_type" value="hostel"><input type="hidden" name="fee_id" value="<?php echo (int)$fee['Service_id']; ?>"><label class="sr-only" for="hostel-fee-<?php echo (int)$fee['Service_id']; ?>">Hostel <?php echo h($fee['Service_name']); ?> fee</label><input id="hostel-fee-<?php echo (int)$fee['Service_id']; ?>" type="number" name="amount" min="0" max="1000000000" step="1" value="<?php echo $fee['Amount'] !== null ? (int)$fee['Amount'] : ''; ?>" placeholder="Unset"><button class="button" type="submit">Save rate</button></form>
					<?php endif; ?>
				</td><td></td></tr><?php endwhile; ?>
			</tbody></table></div></div>
			<div><h3>Messes</h3><div class="table-responsive"><table class="table admin-table"><thead><tr><th>Mess</th><th>Rate (Rs)</th><th></th></tr></thead><tbody>
				<?php while ($fee = mysqli_fetch_assoc($messFees)): ?><tr><td>Mess <?php echo h($fee['Service_name']); ?></td><td>
					<?php if (in_array($fee['Service_name'], array('A', 'G'), true)): ?>Rs <?php echo h(number_format((int)$fee['Amount'])); ?><?php else: ?>
					<form method="post"><input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="save_fee"><input type="hidden" name="fee_type" value="mess"><input type="hidden" name="fee_id" value="<?php echo (int)$fee['Service_id']; ?>"><label class="sr-only" for="mess-fee-<?php echo (int)$fee['Service_id']; ?>">Mess <?php echo h($fee['Service_name']); ?> fee</label><input id="mess-fee-<?php echo (int)$fee['Service_id']; ?>" type="number" name="amount" min="0" max="1000000000" step="1" value="<?php echo $fee['Amount'] !== null ? (int)$fee['Amount'] : ''; ?>" placeholder="Unset"><button class="button" type="submit">Save rate</button></form>
					<?php endif; ?>
				</td><td></td></tr><?php endwhile; ?>
			</tbody></table></div></div>
		</div>
	</section>
	<?php endif; ?>
	<section class="panel directory-panel"><div class="panel-heading"><div><p class="eyebrow">PAYMENT REGISTER</p><h2>Student fee submissions</h2></div></div>
		<div class="table-responsive"><table class="table admin-table"><thead><tr><th>Student</th><th>Choice</th><th>Amount</th><th>Transaction</th><th>Screenshot</th><th>Status / review</th></tr></thead><tbody>
		<?php if (mysqli_num_rows($payments) === 0): ?><tr><td colspan="6" class="empty-state">No payment submissions found.</td></tr><?php endif; ?>
		<?php while ($payment = mysqli_fetch_assoc($payments)): ?><tr>
			<td><?php echo h($payment['Fname'].' '.$payment['Lname']); ?><br><small><?php echo h($payment['Student_id']); ?> · <?php echo h($payment['Dept']); ?></small></td>
			<td>Hostel <?php echo h($payment['Hostel_name'] ?: '—'); ?><br>Mess <?php echo h($payment['Mess_name'] ?: '—'); ?></td>
			<td><?php echo (int)$payment['Status'] === 0 || (int)$payment['Status'] === 1 && empty($payment['Proof_path']) ? '—' : 'Rs ' . h(number_format((float)$payment['Amount'])); ?></td>
			<td><?php echo h($payment['Transaction_id'] ?: '—'); ?><br><small><?php echo h($payment['Submitted_at'] ?: ''); ?></small></td>
			<td><?php if (!empty($payment['Proof_path'])): ?><a href="payment_proof.php?student_id=<?php echo rawurlencode($payment['Student_id']); ?>" target="_blank" rel="noopener">View screenshot</a><?php else: ?>—<?php endif; ?></td>
			<td><span class="status-badge <?php echo h((int)$payment['Status'] === 1 && empty($payment['Proof_path']) ? 'pending' : paymentClass($payment['Status'])); ?>"><?php echo h(paymentLabel($payment['Status'], $payment['Proof_path'])); ?></span>
				<?php if (!empty($payment['Review_note'])): ?><p><?php echo h($payment['Review_note']); ?></p><?php endif; ?>
				<?php if ((int)$payment['Status'] === 2): ?><form method="post" class="decision-form" style="min-width:190px;">
					<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="review_payment"><input type="hidden" name="student_id" value="<?php echo h($payment['Student_id']); ?>">
					<label class="sr-only" for="note-<?php echo h($payment['Student_id']); ?>">Review note</label><input id="note-<?php echo h($payment['Student_id']); ?>" type="text" name="review_note" maxlength="255" placeholder="Optional note">
					<button class="button" type="submit" name="decision" value="approve">Accept</button><button class="button button-outline" type="submit" name="decision" value="reject">Reject</button>
				</form><?php endif; ?>
			</td>
		</tr><?php endwhile; ?>
		</tbody></table></div>
	</section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
