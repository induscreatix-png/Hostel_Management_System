<?php
session_start();
require 'includes/config.inc.php';

if (empty($_SESSION['roll'])) {
	header('Location: index.php');
	exit();
}

$requiredColumns = array('Hostel_id', 'Mess_id', 'Transaction_id', 'Proof_path', 'Submitted_at', 'Reviewed_at', 'Review_note');
$paymentAvailable = true;
foreach ($requiredColumns as $column) {
	$check = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
	$table = 'Payment';
	mysqli_stmt_bind_param($check, 'ss', $table, $column);
	mysqli_stmt_execute($check);
	$exists = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
	mysqli_stmt_close($check);
	if (!$exists) {
		$paymentAvailable = false;
		break;
	}
}
$feeTable = mysqli_query($conn, "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'Service_Fee'");
if (!$feeTable || mysqli_num_rows($feeTable) === 0) {
	$paymentAvailable = false;
}

$hostelOptions = array();
$messOptions = array();
if ($paymentAvailable) {
	$hostels = mysqli_query($conn, "SELECT h.Hostel_id, h.Hostel_name, f.Amount FROM Hostel h JOIN Service_Fee f ON f.Service_type = 'hostel' AND f.Service_id = h.Hostel_id WHERE f.Amount IS NOT NULL ORDER BY h.Hostel_name");
	$messes = mysqli_query($conn, "SELECT m.Mess_id, m.Mess_name, f.Amount FROM Mess m JOIN Service_Fee f ON f.Service_type = 'mess' AND f.Service_id = m.Mess_id WHERE f.Amount IS NOT NULL ORDER BY m.Mess_name");
	if ($hostels) {
		while ($row = mysqli_fetch_assoc($hostels)) {
			$hostelOptions[(int)$row['Hostel_id']] = array('name' => $row['Hostel_name'], 'fee' => (int)$row['Amount']);
		}
	}
	if ($messes) {
		while ($row = mysqli_fetch_assoc($messes)) {
			$messOptions[(int)$row['Mess_id']] = array('name' => $row['Mess_name'], 'fee' => (int)$row['Amount']);
		}
	}
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$notice = '';
$noticeType = 'success';
$studentId = $_SESSION['roll'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(400);
		exit('Invalid request token.');
	}
	$hostelId = filter_input(INPUT_POST, 'hostel_id', FILTER_VALIDATE_INT);
	$messId = filter_input(INPUT_POST, 'mess_id', FILTER_VALIDATE_INT);
	$transactionId = trim(isset($_POST['transaction_id']) ? $_POST['transaction_id'] : '');
	$hostel = $hostelId && isset($hostelOptions[$hostelId]) ? $hostelOptions[$hostelId] : null;
	$mess = $messId && isset($messOptions[$messId]) ? $messOptions[$messId] : null;
	$upload = isset($_FILES['payment_proof']) ? $_FILES['payment_proof'] : null;
	if (!$paymentAvailable) {
		$notice = 'Payment verification is not configured. Run database/payment_review_workflow.sql and contact the administrator.';
		$noticeType = 'error';
	} elseif (!$hostel || !$mess) {
		$notice = 'Select a valid hostel and mess.';
		$noticeType = 'error';
	} elseif ($transactionId === '' || strlen($transactionId) > 120) {
		$notice = 'Enter a transaction ID of up to 120 characters.';
		$noticeType = 'error';
	} elseif (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] < 1 || $upload['size'] > 5 * 1024 * 1024) {
		$notice = 'Upload a payment screenshot no larger than 5 MB.';
		$noticeType = 'error';
	} else {
		$mimeReader = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = $mimeReader ? finfo_file($mimeReader, $upload['tmp_name']) : '';
		if ($mimeReader) {
			finfo_close($mimeReader);
		}
		$allowedTypes = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
		if (!isset($allowedTypes[$mimeType])) {
			$notice = 'Use a JPEG, PNG, or WebP screenshot.';
			$noticeType = 'error';
		} else {
			$uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payment_proofs';
			if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true)) {
				$notice = 'The screenshot folder could not be created. Please contact the administrator.';
				$noticeType = 'error';
			} else {
				$fileName = bin2hex(random_bytes(20)) . '.' . $allowedTypes[$mimeType];
				$filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
				if (!move_uploaded_file($upload['tmp_name'], $filePath)) {
					$notice = 'The screenshot could not be saved. Please try again.';
					$noticeType = 'error';
				} else {
					$relativePath = 'uploads/payment_proofs/' . $fileName;
					$totalAmount = $hostel['fee'] + $mess['fee'];
					$stmt = mysqli_prepare($conn, 'SELECT Status, Proof_path FROM Payment WHERE Student_id = ? LIMIT 1');
					mysqli_stmt_bind_param($stmt, 's', $studentId);
					mysqli_stmt_execute($stmt);
					$existingPayment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
					mysqli_stmt_close($stmt);
					if ($existingPayment && (int)$existingPayment['Status'] === 2) {
						unlink($filePath);
						$notice = 'Your payment is already awaiting admin review.';
						$noticeType = 'error';
					} elseif ($existingPayment && (int)$existingPayment['Status'] === 1 && !empty($existingPayment['Proof_path'])) {
						unlink($filePath);
						$notice = 'Your payment has already been approved.';
						$noticeType = 'error';
					} else {
						$stmt = mysqli_prepare($conn, 'INSERT INTO Payment (Student_id, Status, Amount, Hostel_id, Mess_id, Transaction_id, Proof_path, Submitted_at, Reviewed_at, Review_note) VALUES (?, 2, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, NULL, NULL) ON DUPLICATE KEY UPDATE Status = 2, Amount = VALUES(Amount), Hostel_id = VALUES(Hostel_id), Mess_id = VALUES(Mess_id), Transaction_id = VALUES(Transaction_id), Proof_path = VALUES(Proof_path), Submitted_at = CURRENT_TIMESTAMP, Reviewed_at = NULL, Review_note = NULL');
						mysqli_stmt_bind_param($stmt, 'siiiss', $studentId, $totalAmount, $hostelId, $messId, $transactionId, $relativePath);
						$saved = mysqli_stmt_execute($stmt);
						mysqli_stmt_close($stmt);
						if ($saved) {
							$notice = 'Payment proof submitted. Your payment is pending admin verification.';
						} else {
							unlink($filePath);
							$notice = 'Payment proof could not be submitted. Please try again.';
							$noticeType = 'error';
						}
					}
				}
			}
		}
	}
}

$payment = null;
if ($paymentAvailable) {
	$stmt = mysqli_prepare($conn, 'SELECT Status, Amount, Hostel_id, Mess_id, Transaction_id, Proof_path, Review_note FROM Payment WHERE Student_id = ? LIMIT 1');
	mysqli_stmt_bind_param($stmt, 's', $studentId);
	mysqli_stmt_execute($stmt);
	$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
	mysqli_stmt_close($stmt);
}
$hostelOptionIds = array_keys($hostelOptions);
$messOptionIds = array_keys($messOptions);
$defaultHostelId = $payment && isset($hostelOptions[(int)$payment['Hostel_id']]) ? (int)$payment['Hostel_id'] : (count($hostelOptionIds) ? (int)$hostelOptionIds[0] : 0);
$defaultMessId = $payment && isset($messOptions[(int)$payment['Mess_id']]) ? (int)$payment['Mess_id'] : (count($messOptionIds) ? (int)$messOptionIds[0] : 0);
$legacyPaymentNeedsProof = $payment && (int)$payment['Status'] === 1 && empty($payment['Proof_path']);
$amount = ($payment && in_array((int)$payment['Status'], array(2, -1), true)) || ($payment && (int)$payment['Status'] === 1 && !$legacyPaymentNeedsProof) ? (int)$payment['Amount'] : 0;
$adminAccount = '6543657789065325';
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function paymentStatus($status) {
	if ((int)$status === 1) return 'Approved';
	if ((int)$status === 2) return 'Pending review';
	if ((int)$status === -1) return 'Rejected';
	return 'Unpaid';
}
function paymentStatusWithProof($payment) {
	if (!$payment) return 'Unpaid';
	if ((int)$payment['Status'] === 1 && empty($payment['Proof_path'])) return 'Needs proof';
	return paymentStatus($payment['Status']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Hostel and mess payment | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web_home/css_home/style.css">
	<link rel="stylesheet" href="web/css/student-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="panel payment-panel">
		<p class="eyebrow">STUDENT SERVICES</p><h1>Hostel and mess payment</h1>
		<p class="panel-description">Choose your hostel and mess, pay the total to the admin account, then submit the transaction ID and screenshot for verification.</p>
		<?php if ($notice !== ''): ?><div class="notice <?php echo h($noticeType); ?>" role="status"><?php echo h($notice); ?></div><?php endif; ?>
		<?php if (!$paymentAvailable): ?><div class="notice error">Run <code>database/payment_review_workflow.sql</code> to enable payment review.</div>
		<?php else: ?>
		<div class="payment-summary">
			<div><span>Student ID</span><strong><?php echo h($studentId); ?></strong></div>
			<div><span>Payment status</span><strong><?php echo h(paymentStatusWithProof($payment)); ?></strong></div>
			<div><span>Amount submitted</span><strong><?php echo $amount > 0 ? 'Rs ' . h(number_format($amount)) : '—'; ?></strong></div>
		</div>
		<div class="panel-description"><strong>Admin account number:</strong> <?php echo h($adminAccount); ?></div>
		<?php if ($legacyPaymentNeedsProof): ?><div class="notice">Your existing payment has no screenshot for admin verification. Submit the transaction ID and screenshot to continue.</div><?php endif; ?>
		<?php if ($payment && (int)$payment['Status'] === -1 && !empty($payment['Review_note'])): ?><div class="notice error">Admin note: <?php echo h($payment['Review_note']); ?></div><?php endif; ?>
		<?php if (!$payment || !in_array((int)$payment['Status'], array(1, 2), true) || $legacyPaymentNeedsProof): ?>
		<form method="post" enctype="multipart/form-data" class="request-form payment-confirm">
			<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
			<label>Hostel
				<select name="hostel_id" id="hostel_id" required>
					<?php if (!$hostelOptions): ?><option value="">No hostel rates configured</option><?php endif; ?>
					<?php foreach ($hostelOptions as $id => $option): ?><option value="<?php echo (int)$id; ?>" data-fee="<?php echo (int)$option['fee']; ?>" <?php echo $id === $defaultHostelId ? 'selected' : ''; ?>>Hostel <?php echo h($option['name']); ?> — Rs <?php echo number_format($option['fee']); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label>Mess
				<select name="mess_id" id="mess_id" required>
					<?php if (!$messOptions): ?><option value="">No mess rates configured</option><?php endif; ?>
					<?php foreach ($messOptions as $id => $option): ?><option value="<?php echo (int)$id; ?>" data-fee="<?php echo (int)$option['fee']; ?>" <?php echo $id === $defaultMessId ? 'selected' : ''; ?>>Mess <?php echo h($option['name']); ?> — Rs <?php echo number_format($option['fee']); ?></option><?php endforeach; ?>
				</select>
			</label>
			<p class="panel-description">Total to transfer: <strong id="payment-total">Rs 0</strong> (hostel + mess)</p>
			<label>Transaction ID<input type="text" name="transaction_id" maxlength="120" required></label>
			<label>Payment screenshot<input type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp" required><small>JPEG, PNG, or WebP; maximum 5 MB.</small></label>
			<button class="button" type="submit" <?php echo !$hostelOptions || !$messOptions ? 'disabled' : ''; ?>>Submit payment for review</button>
		</form>
		<?php elseif ((int)$payment['Status'] === 2): ?><p class="notice">Your screenshot and transaction ID are with the admin for review.</p>
		<?php else: ?><p class="notice success">Your payment has been approved. You can apply for hostel or mess services.</p><?php endif; ?>
		<?php endif; ?>
	</section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
(function () {
	var hostel = document.getElementById('hostel_id');
	var mess = document.getElementById('mess_id');
	var total = document.getElementById('payment-total');
	function updateTotal() {
		if (!hostel || !mess || !total || !hostel.options.length || !mess.options.length) return;
		var amount = Number(hostel.options[hostel.selectedIndex].getAttribute('data-fee')) + Number(mess.options[mess.selectedIndex].getAttribute('data-fee'));
		total.textContent = 'Rs ' + amount.toLocaleString('en-IN');
	}
	if (hostel) hostel.addEventListener('change', updateTotal);
	if (mess) mess.addEventListener('change', updateTotal);
	updateTotal();
}());
</script>
</body>
</html>
