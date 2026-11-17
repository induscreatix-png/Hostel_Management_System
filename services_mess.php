<?php
session_start();
require 'includes/config.inc.php';

if (empty($_SESSION['roll'])) {
	header('Location: index.php');
	exit();
}

$messes = mysqli_query($conn, 'SELECT Mess_id, Mess_name, Mess_type, Vacancy, Size FROM Mess ORDER BY Mess_name');
function h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Mess options | Nayab Hostel</title>
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"><link rel="stylesheet" href="web_home/css_home/style.css"><link rel="stylesheet" href="web/css/student-dashboard.css">
</head>
<body class="student-dashboard">
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<main class="student-shell dashboard-main">
	<section class="profile-hero"><div><p class="eyebrow">DINING OPTIONS</p><h1>Choose your mess</h1><p>Browse available options and follow your application from your profile.</p></div></section>
	<div class="mess-grid">
		<?php if (mysqli_num_rows($messes) === 0): ?><section class="panel"><p class="empty-state">No mess options are configured yet. Contact the administrator.</p></section><?php endif; ?>
		<?php while ($mess = mysqli_fetch_assoc($messes)): ?>
		<article class="panel mess-card">
			<div class="mess-card-top"><span class="mess-icon" aria-hidden="true">M</span><span class="mess-type"><?php echo h($mess['Mess_type']); ?></span></div>
			<h2>Mess <?php echo h($mess['Mess_name']); ?></h2>
			<p><?php echo (int)$mess['Vacancy']; ?> of <?php echo (int)$mess['Size']; ?> cards available</p>
			<?php if ((int)$mess['Vacancy'] > 0): ?><a class="button" href="application_form_mess.php?id=<?php echo rawurlencode($mess['Mess_name']); ?>">Apply for this mess</a><?php else: ?><span class="button button-outline disabled-link" aria-disabled="true">No vacancies</span><?php endif; ?>
		</article>
		<?php endwhile; ?>
	</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
