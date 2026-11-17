<?php
session_start();
require '../includes/config.inc.php';

$isAdmin = isset($_SESSION['isadmin']) && (int)$_SESSION['isadmin'] === 1;
if (!$isAdmin) {
	require '../includes/manager_access.php';
	requireManagerAccess($conn, array('Finance Reviewer'), '../login-hostel_manager.php', 'admin_home.php');
}
if (empty($_SESSION['hostel_man_id']) || (!isset($_SESSION['isadmin']) || (int)$_SESSION['isadmin'] !== 1) && (!isset($_SESSION['position']) || $_SESSION['position'] !== 'Finance Reviewer')) {
	http_response_code(403);
	exit('Forbidden.');
}
$studentId = trim(isset($_GET['student_id']) ? $_GET['student_id'] : '');
if ($studentId === '') {
	http_response_code(400);
	exit('A student ID is required.');
}
$stmt = mysqli_prepare($conn, 'SELECT Proof_path FROM Payment WHERE Student_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $studentId);
mysqli_stmt_execute($stmt);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$payment || empty($payment['Proof_path'])) {
	http_response_code(404);
	exit('Payment screenshot not found.');
}
$uploadDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payment_proofs');
$proofPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $payment['Proof_path']);
if (!$uploadDirectory || !$proofPath || strpos($proofPath, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0 || !is_file($proofPath)) {
	http_response_code(404);
	exit('Payment screenshot not found.');
}
$mimeReader = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $mimeReader ? finfo_file($mimeReader, $proofPath) : '';
if ($mimeReader) {
	finfo_close($mimeReader);
}
if (!in_array($mimeType, array('image/jpeg', 'image/png', 'image/webp'), true)) {
	http_response_code(415);
	exit('Unsupported screenshot type.');
}
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($proofPath));
header('Content-Disposition: inline; filename="payment-proof"');
header('X-Content-Type-Options: nosniff');
readfile($proofPath);
