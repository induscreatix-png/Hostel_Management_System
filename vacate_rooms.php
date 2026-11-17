<?php
session_start();
  require 'includes/config.inc.php';
require 'includes/manager_access.php';
requireManagerAccess($conn, array('Warden', 'Hostel Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');

$vacateNotice = isset($_SESSION['room_vacate_notice']) ? $_SESSION['room_vacate_notice'] : '';
unset($_SESSION['room_vacate_notice']);

if (isset($_POST['submit'])) {
	$hostelId = isset($_SESSION['hostel_id']) ? (int)$_SESSION['hostel_id'] : 0;
	$studentId = isset($_POST['roll_no']) ? trim($_POST['roll_no']) : '';
	$roomNumber = isset($_POST['room_no']) ? filter_var($_POST['room_no'], FILTER_VALIDATE_INT) : false;
	if ($hostelId < 1 || $studentId === '' || !$roomNumber || $roomNumber < 1) {
		$_SESSION['room_vacate_notice'] = 'Enter a valid student ID and room number.';
		header('Location: vacate_rooms.php');
		exit();
	}

	mysqli_begin_transaction($conn);
	$roomStmt = mysqli_prepare($conn, 'SELECT Room_id, Allocated FROM Room WHERE Hostel_id = ? AND Room_No = ? FOR UPDATE');
	mysqli_stmt_bind_param($roomStmt, 'ii', $hostelId, $roomNumber);
	mysqli_stmt_execute($roomStmt);
	$room = mysqli_fetch_assoc(mysqli_stmt_get_result($roomStmt));
	mysqli_stmt_close($roomStmt);

	if (!$room || (int)$room['Allocated'] < 1) {
		mysqli_rollback($conn);
		$_SESSION['room_vacate_notice'] = 'That room does not have any allocated students.';
	} else {
		$roomId = (int)$room['Room_id'];
		$studentStmt = mysqli_prepare($conn, 'SELECT Student_id FROM Student WHERE Student_id = ? AND Hostel_id = ? AND Room_id = ? FOR UPDATE');
		mysqli_stmt_bind_param($studentStmt, 'sii', $studentId, $hostelId, $roomId);
		mysqli_stmt_execute($studentStmt);
		$student = mysqli_fetch_assoc(mysqli_stmt_get_result($studentStmt));
		mysqli_stmt_close($studentStmt);
		if (!$student) {
			mysqli_rollback($conn);
			$_SESSION['room_vacate_notice'] = 'That student is not assigned to the selected room.';
		} else {
			$releaseStmt = mysqli_prepare($conn, 'UPDATE Student SET Hostel_id = NULL, Room_id = NULL WHERE Student_id = ? AND Room_id = ?');
			mysqli_stmt_bind_param($releaseStmt, 'si', $studentId, $roomId);
			mysqli_stmt_execute($releaseStmt);
			$studentReleased = mysqli_stmt_affected_rows($releaseStmt) === 1;
			mysqli_stmt_close($releaseStmt);

			$roomStmt = mysqli_prepare($conn, 'UPDATE Room SET Allocated = Allocated - 1 WHERE Room_id = ? AND Allocated > 0');
			mysqli_stmt_bind_param($roomStmt, 'i', $roomId);
			mysqli_stmt_execute($roomStmt);
			$roomUpdated = mysqli_stmt_affected_rows($roomStmt) === 1;
			mysqli_stmt_close($roomStmt);
			if (!$studentReleased || !$roomUpdated) {
				mysqli_rollback($conn);
				$_SESSION['room_vacate_notice'] = 'The room allocation changed during this request. No changes were saved.';
			} else {
				$deleteStmt = mysqli_prepare($conn, 'DELETE FROM Application WHERE Student_id = ?');
				mysqli_stmt_bind_param($deleteStmt, 's', $studentId);
				mysqli_stmt_execute($deleteStmt);
				mysqli_stmt_close($deleteStmt);
				mysqli_commit($conn);
				$_SESSION['room_vacate_notice'] = 'Student successfully removed from the room.';
			}
		}
	}
	header('Location: vacate_rooms.php');
	exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>VACATE ROOMS | Nayab Hostel</title>

	<!-- Meta tag Keywords -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<meta name="keywords" content="Intrend Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
	Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
	<script type="application/x-javascript">
		addEventListener("load", function () {
			setTimeout(hideURLbar, 0);
		}, false);

		function hideURLbar() {
			window.scrollTo(0, 1);
		}
	</script>
	<!--bootsrap -->

	<!--// Meta tag Keywords -->

	<!-- css files -->
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS -->
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> <!-- Font-Awesome-Icons-CSS -->
	<!-- //css files -->

	<!-- web-fonts -->
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
	<!-- //web-fonts -->

</head>

<body>

<!-- banner -->
<div class="inner-page-banner" id="home">
	<!--Header-->
	<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
	<!--Header-->
</div>

<br><br><br>
<?php
   $hostel_id = $_SESSION['hostel_id'];
   $query1 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
   $result1 = mysqli_query($conn,$query1);
   $row1 = mysqli_fetch_assoc($result1);
   $hostel_name = $row1['Hostel_name'];
?>

<section class="contact py-5">
	<div class="container">
		<h2 class="heading text-capitalize mb-sm-5 mb-4"> Vacate Form </h2>
		<?php if ($vacateNotice !== ''): ?><div class="alert alert-info" role="status"><?php echo htmlspecialchars($vacateNotice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
			<div class="mail_grid_w3l">
				<form action="vacate_rooms.php" method="post">
					<div class="row">
						<div class="col-md-6 contact_left_grid" data-aos="fade-right">
							<div class="contact-fields-w3ls">
								<input type="text" name="roll_no" placeholder="Roll Number" required >
							</div>
							<div class="contact-fields-w3ls">
								<input type="text" name="hostel" placeholder="Hostel" value="<?php echo $hostel_name;?>" required="" disabled="disabled">
							</div>
							<div class="contact-fields-w3ls">
								<input type="number" name="room_no" placeholder="Room Number" required="">
							</div>
						</div>
						<div class="col-md-6 contact_left_grid" data-aos="fade-left">
							<input type="submit" name="submit" value="Click to Vacate">
						</div>
					</div>

				</form>
			</div>

	</div>
</section>
<br><br><br>

<!-- footer -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<!-- footer -->

<!-- js-scripts -->

	<!-- js -->
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap -->
	<!-- //js -->

	<!-- banner js -->
	<script src="web_home/js/snap.svg-min.js"></script>
	<script src="web_home/js/main.js"></script> <!-- Resource jQuery -->
	<!-- //banner js -->

	<!-- flexSlider --><!-- for testimonials -->
	<script defer src="web_home/js/jquery.flexslider.js"></script>
	<script type="text/javascript">
		$(window).load(function(){
		  $('.flexslider').flexslider({
			animation: "slide",
			start: function(slider){
			  $('body').removeClass('loading');
			}
		  });
		});
	</script>
	<!-- //flexSlider --><!-- for testimonials -->

	<!-- start-smoth-scrolling -->
	<script src="web_home/js/SmoothScroll.min.js"></script>
	<script type="text/javascript" src="web_home/js/move-top.js"></script>
	<script type="text/javascript" src="web_home/js/easing.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".scroll").click(function(event){
				event.preventDefault();
				$('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
			});
		});
	</script>
	<!-- here stars scrolling icon -->
	<script type="text/javascript">
		$(document).ready(function() {
			/*
				var defaults = {
				containerID: 'toTop', // fading element id
				containerHoverID: 'toTopHover', // fading element hover id
				scrollSpeed: 1200,
				easingType: 'linear'
				};
			*/

			$().UItoTop({ easingType: 'easeOutQuart' });

			});
	</script>
	<!-- //here ends scrolling icon -->
	<!-- start-smoth-scrolling -->

<!-- //js-scripts -->

</body>
</html>
