<?php
session_start();
  require 'includes/config.inc.php';
require 'includes/manager_access.php';
requireManagerAccess($conn, array('Mess Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');
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
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
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

<br><br><br>
<?php
   $mess_id = $_SESSION['Mess_id'];
   $query1 = "SELECT * FROM Mess WHERE Mess_id = '$mess_id'";
   $result1 = mysqli_query($conn,$query1);
   $row1 = mysqli_fetch_assoc($result1);
   $mess_name = $row1['Mess_name'];
?>

<section class="contact py-5">
	<div class="container">
		<h2 class="heading text-capitalize mb-sm-5 mb-4"> Vacate Form </h2>
			<div class="mail_grid_w3l">
				<form action="vacate_mess.php" method="post">
					<div class="row">
						<div class="col-md-6 contact_left_grid" data-aos="fade-right">
							<div class="contact-fields-w3ls">
								<input type="text" name="roll_no" placeholder="Roll Number" required >
							</div>
							<div class="contact-fields-w3ls">
								<input type="text" name="mess" placeholder="Mess" value="<?php echo $mess_name;?>" required="" disabled="disabled">
							</div>
							<div class="contact-fields-w3ls">
								<input type="number" name="mess_card_no" placeholder="Mess Card Number" required>
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
<?php
if(isset($_POST['submit'])){
     $roll = $_POST['roll_no'];
     if(isset($_POST['mess']))
     	$mess = $_POST['mess'];
     $mess_card_number =(int)$_POST['mess_card_no'];

    $query2 = "SELECT * FROM Mess_Allocation WHERE Mess_id = '$mess_id' and Mess_card_No = '$mess_card_number'";
    $result2 = mysqli_query($conn,$query2);
    if(mysqli_num_rows($result2)==0){
        echo "<script type='text/javascript'>alert('Incorrect Details')</script>";
        exit();
    }
    $row2 = mysqli_fetch_assoc($result2);
    if($row2['Allocated']=='0'){
    	echo "<script type='text/javascript'>alert('Mess ID Not Allocated')</script>";
    	exit();
    }
    $mess_card_id = (int)$row2['Mess_card_id'];
    //echo "<script type='text/javascript'>alert(' $room_id, $mess_card_number ')</script>";
	$query3 = "SELECT * FROM Student WHERE Student_id = '$roll' and Mess_id = '$mess_id' and Mess_card_id = '$mess_card_id'";
	$result3 = mysqli_query($conn,$query3);
    if(mysqli_num_rows($result3)==0){
        echo "<script type='text/javascript'>alert('Incorrect Details 2')</script>";
        exit();
    }
    $row3 = mysqli_fetch_assoc($result3);
    if($result3){
    	$query4 = "UPDATE Student SET Mess_id = NULL, Mess_card_id = NULL WHERE Student_id = '$roll'";
    	$result4 = mysqli_query($conn,$query4);
    	if($result4){
    		$query5 = "UPDATE Mess_Allocation SET Allocated = '0' WHERE Mess_card_id = '$mess_card_id'";
    		$result5 = mysqli_query($conn,$query5);
    		if($result5){
				$recount = mysqli_prepare($conn, 'UPDATE Mess SET Vacancy = GREATEST(0, Size - (SELECT COUNT(*) FROM Mess_Allocation WHERE Mess_id = ? AND Allocated = 1)) WHERE Mess_id = ?');
				mysqli_stmt_bind_param($recount, 'ii', $mess_id, $mess_id);
				mysqli_stmt_execute($recount);
				mysqli_stmt_close($recount);
    			//$query7 = "UPDATE Mess SET current_no_of_rooms=current_no_of_rooms - 1,No_of_students=No_of_students - 1 WHERE Mess_id='$mess_id'";
    			//$result7 = mysqli_query($conn,$query7);
    			//if($result7){
    				$query6 = "DELETE FROM Application_mess WHERE Student_id = '$roll'";
    				$result6 = mysqli_query($conn,$query6);
    				if($result6){
    				    echo "<script type='text/javascript'>alert('Vacated Successfully')</script>";
    				
    				}

    		}
    	}
    }
}


?>

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
