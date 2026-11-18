<?php
session_start();
  require 'includes/config.inc.php';
require 'includes/manager_access.php';
requireManagerAccess($conn, array('Mess Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Allocate mess | Nayab Hostel</title>

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
	<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
</div>
<!-- //banner -->
<br><br><br>

<section class="contact py-5">
	<div class="container">
			<div class="mail_grid_w3l">
				<form action="allocate_mess_card.php" method="post">
					<div class="row">
					        <div class="col-md-9">
							<input type="text" placeholder="Search by Roll Number" name="search_box">
							</div>
							<div class="col-md-3">
							<input type="submit" value="Search" name="searchmesscard"></input>
							</div>
					</div>
				</form>
			</div>
	</div>
</section>

<?php
   if (isset($_POST['searchmesscard'])) {
   	   $search_box = $_POST['search_box'];
   	   $mess_id = $_SESSION['Mess_id'];
   	   $query_search = "SELECT * FROM Application_mess WHERE Student_id like '$search_box%' and Mess_id = '$mess_id' and Application_status = '1'";
   	   $result_search = mysqli_query($conn,$query_search);

   	   //select the mess name from mess table
       $query6 = "SELECT * FROM Mess WHERE Mess_id = '$mess_id'";
       $result6 = mysqli_query($conn,$query6);
       $row6 = mysqli_fetch_assoc($result6);
       $mess_name = $row6['Mess_name'];
   	   ?>
   	   <div class="container">
   	   <table class="table table-hover">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Roll No</th>
        <th>Mess</th>
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

      		echo "<tr><td>{$student_name}</td><td>{$row_search['Student_id']}</td><td>{$mess_name}</td><td>{$row_search['Message']}</td></tr>\n";

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
<?php
   $mess_id = $_SESSION['Mess_id'];
   $query1 = "SELECT * FROM Application_mess where Mess_id = '$mess_id' and Application_status = '1'";
   $result1 = mysqli_query($conn,$query1);

   //select the mess name from mess table
   $query6 = "SELECT * FROM Mess WHERE Mess_id = '$mess_id'";
   $result6 = mysqli_query($conn,$query6);
   $row6 = mysqli_fetch_assoc($result6);
   $mess_name = $row6['Mess_name'];
?>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Roll No</th>
        <th>Mess</th>
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

      		echo "<tr><td>{$student_name}</td><td>{$row1['Student_id']}</td><td>{$mess_name}</td><td>{$row1['Message']}</td></tr>\n";
      	}
      }
    ?>
    </tbody>
  </table>
</div>
<section class="contact py-5">
	<div class="container">
			<div class="mail_grid_w3l">
				<form action="allocate_mess_card.php" method="post">
					<div class="row">
							<input type="submit" value="Allocate" name="submitallo">
					</div>
				</form>
			</div>
	</div>
</section>
<?php
if(isset($_POST['submitallo'])){
	$mess_id = (int)$_SESSION['Mess_id'];
	$allocatedCount = 0;
	mysqli_begin_transaction($conn);
	$pendingStmt = mysqli_prepare($conn, 'SELECT Application_id, Student_id FROM Application_mess WHERE Mess_id = ? AND Application_status = 1 ORDER BY Application_id FOR UPDATE');
	mysqli_stmt_bind_param($pendingStmt, 'i', $mess_id);
	mysqli_stmt_execute($pendingStmt);
	$pendingApplications = mysqli_stmt_get_result($pendingStmt);
	while ($application = mysqli_fetch_assoc($pendingApplications)) {
		$cardStmt = mysqli_prepare($conn, 'SELECT Mess_card_id, Mess_card_No FROM Mess_Allocation WHERE Mess_id = ? AND Allocated = 0 ORDER BY Mess_card_No LIMIT 1 FOR UPDATE');
		mysqli_stmt_bind_param($cardStmt, 'i', $mess_id);
		mysqli_stmt_execute($cardStmt);
		$card = mysqli_fetch_assoc(mysqli_stmt_get_result($cardStmt));
		mysqli_stmt_close($cardStmt);
		if (!$card) {
			break;
		}

		$cardId = (int)$card['Mess_card_id'];
		$cardNumber = (int)$card['Mess_card_No'];
		$studentId = $application['Student_id'];
		$applicationId = (int)$application['Application_id'];
		$updateCard = mysqli_prepare($conn, 'UPDATE Mess_Allocation SET Allocated = 1 WHERE Mess_card_id = ? AND Allocated = 0');
		mysqli_stmt_bind_param($updateCard, 'i', $cardId);
		mysqli_stmt_execute($updateCard);
		$cardUpdated = mysqli_stmt_affected_rows($updateCard) === 1;
		mysqli_stmt_close($updateCard);
		if (!$cardUpdated) {
			continue;
		}
		$updateStudent = mysqli_prepare($conn, 'UPDATE Student SET Mess_id = ?, Mess_card_id = ? WHERE Student_id = ? AND Mess_card_id IS NULL');
		mysqli_stmt_bind_param($updateStudent, 'iis', $mess_id, $cardId, $studentId);
		mysqli_stmt_execute($updateStudent);
		$studentUpdated = mysqli_stmt_affected_rows($updateStudent) === 1;
		mysqli_stmt_close($updateStudent);
		if (!$studentUpdated) {
			mysqli_rollback($conn);
			exit('Mess allocation was cancelled because a student allocation changed.');
		}
		$updateApplication = mysqli_prepare($conn, 'UPDATE Application_mess SET Application_status = 0, Mess_card_No = ? WHERE Application_id = ? AND Application_status = 1');
		mysqli_stmt_bind_param($updateApplication, 'ii', $cardNumber, $applicationId);
		mysqli_stmt_execute($updateApplication);
		mysqli_stmt_close($updateApplication);
		$allocatedCount++;
	}
	mysqli_stmt_close($pendingStmt);
	$recount = mysqli_prepare($conn, 'UPDATE Mess SET Vacancy = GREATEST(0, Size - (SELECT COUNT(*) FROM Mess_Allocation WHERE Mess_id = ? AND Allocated = 1)) WHERE Mess_id = ?');
	mysqli_stmt_bind_param($recount, 'ii', $mess_id, $mess_id);
	mysqli_stmt_execute($recount);
	mysqli_stmt_close($recount);
	mysqli_commit($conn);
	if ($allocatedCount > 0) {
		echo "<script>alert('Mess cards allocated: " . (int)$allocatedCount . "');</script>";
	} else {
		echo "<script>alert('No pending applications could be allocated. Check card availability.');</script>";
	}
}
?>
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
