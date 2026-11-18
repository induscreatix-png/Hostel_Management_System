<?php
session_start();
  require 'includes/config.inc.php';
require 'includes/manager_access.php';
requireManagerAccess($conn, array('Warden', 'Hostel Manager'), 'login-hostel_manager.php', 'admin/admin_home.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Rooms with Available Beds | Nayab Hostel</title>

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
				<form action="empty_rooms.php" method="post">
					<div class="row">
					        <div class="col-md-9">
							<input type="text" placeholder="Search by Room Number" name="search_box">
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
   	   $query_search = "SELECT * FROM Room WHERE Room_No like '$search_box%' and Hostel_id = '$hostel_id' and Allocated < Capacity";
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
        <th>Hostel Name</th>
        <th>Room Number</th>
        <th>Students</th>
        <th>Available beds</th>
      </tr>
    </thead>
    <tbody>
    <?php
   	   if(mysqli_num_rows($result_search)==0){
   	   	  echo '<tr><td colspan="4">No Rows Returned</td></tr>';
   	   }
   	   else{
   	   	  while($row_search = mysqli_fetch_assoc($result_search)){

      		echo "<tr><td>{$hostel_name}</td><td>{$row_search['Room_No']}</td><td>{$row_search['Allocated']} / {$row_search['Capacity']}</td><td>" . ((int)$row_search['Capacity'] - (int)$row_search['Allocated']) . "</td></tr>\n";
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
<h2 class="heading text-capitalize mb-sm-5 mb-4"> Rooms with Available Beds </h2>
<?php
   $hostel_id = $_SESSION['hostel_id'];
   $query1 = "SELECT * FROM Room where Hostel_id = '$hostel_id' and Allocated < Capacity";
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
        <th>Hostel Name</th>
        <th>Room Number</th>
        <th>Students</th>
        <th>Available beds</th>
      </tr>
    </thead>
    <tbody>
    <?php
      if(mysqli_num_rows($result1)==0){
         echo '<tr><td colspan="4">No Rows Returned</td></tr>';
      }
      else{
      	while($row1 = mysqli_fetch_assoc($result1)){
      		echo "<tr><td>{$hostel_name}</td><td>{$row1['Room_No']}</td><td>{$row1['Allocated']} / {$row1['Capacity']}</td><td>" . ((int)$row1['Capacity'] - (int)$row1['Allocated']) . "</td></tr>\n";
      	}
      }
    ?>
    </tbody>
  </table>
</div>
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
