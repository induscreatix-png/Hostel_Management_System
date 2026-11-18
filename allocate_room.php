<?php
session_start();
  require 'includes/config.inc.php';
require 'includes/manager_access.php';
requireManagerAccess($conn, array('Warden', 'Hostel Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');

$allocationNotice = isset($_SESSION['room_allocation_notice']) ? $_SESSION['room_allocation_notice'] : '';
unset($_SESSION['room_allocation_notice']);

if (isset($_POST['submitallo'])) {
	$hostelId = isset($_SESSION['hostel_id']) ? (int)$_SESSION['hostel_id'] : 0;
	if ($hostelId < 1) {
		$_SESSION['room_allocation_notice'] = 'Your manager account is not assigned to a hostel.';
		header('Location: allocate_room.php');
		exit();
	}

	mysqli_begin_transaction($conn);
	$pendingStmt = mysqli_prepare($conn, 'SELECT Application_id, Student_id FROM Application WHERE Hostel_id = ? AND Application_status = 1 ORDER BY Application_id FOR UPDATE');
	if (!$pendingStmt) {
		mysqli_rollback($conn);
		$_SESSION['room_allocation_notice'] = 'Could not load pending room applications.';
		header('Location: allocate_room.php');
		exit();
	}
	mysqli_stmt_bind_param($pendingStmt, 'i', $hostelId);
	if (!mysqli_stmt_execute($pendingStmt)) {
		mysqli_stmt_close($pendingStmt);
		mysqli_rollback($conn);
		$_SESSION['room_allocation_notice'] = 'Could not load pending room applications.';
		header('Location: allocate_room.php');
		exit();
	}
	$pendingApplications = mysqli_stmt_get_result($pendingStmt);
	$allocatedCount = 0;
	$allocationFailed = false;
	$noRoomAvailable = false;

	while ($application = mysqli_fetch_assoc($pendingApplications)) {
		$roomStmt = mysqli_prepare($conn, 'SELECT Room_id, Room_No FROM Room WHERE Hostel_id = ? AND Allocated < Capacity ORDER BY Room_No LIMIT 1 FOR UPDATE');
		if (!$roomStmt) {
			$allocationFailed = true;
			break;
		}
		mysqli_stmt_bind_param($roomStmt, 'i', $hostelId);
		if (!mysqli_stmt_execute($roomStmt)) {
			mysqli_stmt_close($roomStmt);
			$allocationFailed = true;
			break;
		}
		$room = mysqli_fetch_assoc(mysqli_stmt_get_result($roomStmt));
		mysqli_stmt_close($roomStmt);
		if (!$room) {
			$noRoomAvailable = true;
			break;
		}

		$roomId = (int)$room['Room_id'];
		$roomNumber = (int)$room['Room_No'];
		$studentId = $application['Student_id'];
		$occupancyStmt = mysqli_prepare($conn, 'UPDATE Room SET Allocated = Allocated + 1 WHERE Room_id = ? AND Allocated < Capacity');
		if (!$occupancyStmt) {
			$allocationFailed = true;
			break;
		}
		mysqli_stmt_bind_param($occupancyStmt, 'i', $roomId);
		mysqli_stmt_execute($occupancyStmt);
		$roomUpdated = mysqli_stmt_affected_rows($occupancyStmt) === 1;
		mysqli_stmt_close($occupancyStmt);
		if (!$roomUpdated) {
			$allocationFailed = true;
			break;
		}

		$studentStmt = mysqli_prepare($conn, 'UPDATE Student SET Hostel_id = ?, Room_id = ? WHERE Student_id = ? AND Room_id IS NULL');
		if (!$studentStmt) {
			$allocationFailed = true;
			break;
		}
		mysqli_stmt_bind_param($studentStmt, 'iis', $hostelId, $roomId, $studentId);
		mysqli_stmt_execute($studentStmt);
		$studentUpdated = mysqli_stmt_affected_rows($studentStmt) === 1;
		mysqli_stmt_close($studentStmt);
		if (!$studentUpdated) {
			$allocationFailed = true;
			break;
		}

		$applicationStmt = mysqli_prepare($conn, 'UPDATE Application SET Application_status = 0, Room_No = ? WHERE Application_id = ? AND Application_status = 1');
		if (!$applicationStmt) {
			$allocationFailed = true;
			break;
		}
		mysqli_stmt_bind_param($applicationStmt, 'ii', $roomNumber, $application['Application_id']);
		mysqli_stmt_execute($applicationStmt);
		$applicationUpdated = mysqli_stmt_affected_rows($applicationStmt) === 1;
		mysqli_stmt_close($applicationStmt);
		if (!$applicationUpdated) {
			$allocationFailed = true;
			break;
		}
		$allocatedCount++;
	}
	mysqli_stmt_close($pendingStmt);

	if ($allocationFailed) {
		mysqli_rollback($conn);
		$_SESSION['room_allocation_notice'] = 'Room allocation failed. No changes were saved; please retry.';
	} else {
		mysqli_commit($conn);
		$_SESSION['room_allocation_notice'] = $allocatedCount . ' student(s) allocated.' . ($noRoomAvailable ? ' Some applications remain pending because the hostel has no available beds.' : '');
	}
	header('Location: allocate_room.php');
	exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Allocate rooms | Nayab Hostel</title>

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
	<link rel="stylesheet" href="web/css/staff-dashboard.css?v=1">
	<!-- //css files -->

	<!-- web-fonts -->
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
	<!-- //web-fonts -->

</head>

<body class="staff-workspace">

<!-- banner -->
<div class="inner-page-banner" id="home">
	<!--Header-->
	<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
	<!--Header-->
</div>
<!-- //banner -->
<br><br><br>

<section class="contact py-5">
	<div class="container">
			<div class="mail_grid_w3l">
				<form action="allocate_room.php" method="post">
					<div class="row">
					        <div class="col-md-9">
							<input type="text" placeholder="Search by Roll Number" name="search_box">
							</div>
							<div class="col-md-3">
							<input type="submit" value="Search" name="search"></input>
							</div>
					</div>
				</form>
			</div>
	</div>
</section>

<?php
   if (isset($_POST['search'])) {
   	   $search_box = $_POST['search_box'];
   	   $hostel_id = $_SESSION['hostel_id'];
   	   $query_search = "SELECT * FROM Application WHERE Student_id like '$search_box%' and Hostel_id = '$hostel_id' and Application_status = '1'";
   	   $result_search = mysqli_query($conn,$query_search);

   	   //select the hostel name from hostel table
       $query6 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
       $result6 = mysqli_query($conn,$query6);
       $row6 = mysqli_fetch_assoc($result6);
       $hostel_name = $row6['Hostel_name'];
   	   ?>
   	   <div class="container">
   	   <table class="table table-hover">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Roll No</th>
        <th>Hostel</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
    <?php
   	   if(mysqli_num_rows($result_search)==0){
   	   	  echo '<tr><td colspan="4">No Rows Returned</td></tr>';
   	   }
   	   else{
   	   	  while($row_search = mysqli_fetch_assoc($result_search)){
      		//get the name of the student to display
            $student_id = $row_search['Student_id'];

            $query7 = "SELECT * FROM Student WHERE Student_id = '$student_id'";
            $result7 = mysqli_query($conn,$query7);
            $row7 = mysqli_fetch_assoc($result7);
            $student_name = $row7['Fname']." ".$row7['Lname'];

      		echo "<tr><td>{$student_name}</td><td>{$row_search['Student_id']}</td><td>{$hostel_name}</td><td>{$row_search['Message']}</td></tr>\n";

   	   	  }
   	   }
   ?>
   </tbody>
  </table>
</div>
<?php
}
  ?>

<div class="container">
<h2 class="heading text-capitalize mb-sm-5 mb-4"> Applications Received </h2>
<?php if ($allocationNotice !== ''): ?><div class="alert alert-info" role="status"><?php echo htmlspecialchars($allocationNotice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php
   $hostel_id = $_SESSION['hostel_id'];
   $query1 = "SELECT * FROM Application where Hostel_id = '$hostel_id' and Application_status = '1'";
   $result1 = mysqli_query($conn,$query1);
   //select the hostel name from hostel table
   $query6 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
   $result6 = mysqli_query($conn,$query6);
   $row6 = mysqli_fetch_assoc($result6);
   $hostel_name = $row6['Hostel_name'];
?>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Roll No</th>
        <th>Hostel</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
    <?php
      if(mysqli_num_rows($result1)==0){
         echo '<tr><td colspan="4">Empty</td></tr>';
      }
      else{
      	while($row1 = mysqli_fetch_assoc($result1)){
      		//get the name of the student to display
            $student_id = $row1['Student_id'];
            $query7 = "SELECT * FROM Student WHERE Student_id = '$student_id'";
            $result7 = mysqli_query($conn,$query7);
            $row7 = mysqli_fetch_assoc($result7);
            $student_name = $row7['Fname']." ".$row7['Lname'];

      		echo "<tr><td>{$student_name}</td><td>{$row1['Student_id']}</td><td>{$hostel_name}</td><td>{$row1['Message']}</td></tr>\n";
      	}
      }
    ?>
    </tbody>
  </table>
</div>
<section class="contact py-5">
	<div class="container">
			<div class="mail_grid_w3l">
				<form action="allocate_room.php" method="post">
					<div class="row">
							<input type="submit" value="Allocate" name="submitallo">
					</div>
				</form>
			</div>
	</div>
</section>
<br>
<br>
<br>

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
