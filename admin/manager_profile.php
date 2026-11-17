<?php
session_start();
		  require '../includes/config.inc.php';
require '../includes/manager_access.php';
$manager = requireManagerAccess($conn, array('Warden', 'Hostel Manager', 'Mess Manager', 'Finance Reviewer'), '../login-hostel_manager.php', 'admin_home.php');
$profileStmt = mysqli_prepare($conn, 'SELECT hm.Username, hm.Fname, hm.Lname, hm.Mob_no, hm.Position, h.Hostel_name, m.Mess_name, CASE WHEN h.Hostel_id IS NOT NULL AND h.No_of_rooms > 0 THEN ROUND(100 * h.current_no_of_rooms / h.No_of_rooms, 1) ELSE NULL END AS Hostel_Occupancy, CASE WHEN m.Mess_id IS NOT NULL AND m.Size > 0 THEN ROUND(100 * (m.Size - m.Vacancy) / m.Size, 1) ELSE NULL END AS Mess_Occupancy FROM Hostel_Manager hm LEFT JOIN Hostel h ON h.Hostel_id = hm.Hostel_id LEFT JOIN Mess m ON m.Mess_id = hm.Mess_id WHERE hm.Hostel_man_id = ? LIMIT 1');
if (!$profileStmt) {
	http_response_code(500);
	exit('Could not load your profile.');
}
mysqli_stmt_bind_param($profileStmt, 'i', $manager['Hostel_man_id']);
mysqli_stmt_execute($profileStmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStmt));
mysqli_stmt_close($profileStmt);
if (!$row) {
	http_response_code(404);
	exit('Staff profile not found.');
}
$Fname = $row['Fname'];
$Lname = $row['Lname'];
$Uname = $row['Username'];
$Mob = $row['Mob_no'];
$Hostel_name = $row['Hostel_name'];
$Mess_name = $row['Mess_name'];
$Hostel_Occupancy = $row['Hostel_Occupancy'];
$Mess_Occupancy = $row['Mess_Occupancy'];
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>User Profile | Nayab Hostel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Consultancy Profile Widget Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
		function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- js -->
<script src="../web_profile/js/jquery-2.1.3.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../web_profile/js/sliding.form.js"></script>
<!-- //js -->
<link href="../web_profile/css/style.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" href="../web_profile/css/font-awesome.min.css" />
<link rel="stylesheet" href="../web_profile/css/smoothbox.css" type='text/css' media="all" />
<link href="//fonts.googleapis.com/css?family=Pathway+Gothic+One" rel="stylesheet">
<link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>

<script type="application/x-javascript">
	addEventListener("load", function () {
		setTimeout(hideURLbar, 0);
	}, false);

	function hideURLbar() {
		window.scrollTo(0, 1);
	}
</script>
<!--// Meta tag Keywords -->

<link href="../web_home/css_home/slider.css" type="text/css" rel="stylesheet" media="all">

<!-- css files -->
<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS -->
<link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css"> <!-- Font-Awesome-Icons-CSS -->
<!-- //css files -->

<!-- testimonials css -->
<link rel="stylesheet" href="../web_home/css_home/flexslider.css" type="text/css" media="screen" property="" /><!-- flexslider css -->
<!-- //testimonials css -->

<!-- web-fonts -->
<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
<!-- //web-fonts -->


</head>
<body>
	<!-- banner -->
		<div class="banner" id="home">
			<div class="cd-radial-slider-wrapper">

	<!--Header-->
	<?php require_once __DIR__ . '/../includes/site_chrome.php'; renderSiteHeader(); ?>
	<!--Header-->
<br><br><br><br><br>
	<div class="main">
		<div id="navigation" style="display:none;" class="w3_agile">
      <ul>
				<li class="selected">
					<a href="#"><i class="fa fa-list-ul" aria-hidden="true"></i><span>Info</span></a>
				</li>
				<li>
					<a href="#"><i class="fa fa-folder" aria-hidden="true"></i><span>Admin</span></a>
				</li>
			</ul>
		</div>
		<div id="wrapper" class="w3ls_wrapper w3layouts_wrapper">
			<div id="steps" style="margin:0 auto;" class="agileits w3_steps">
				<form id="formElem" name="formElem" action="#" method="post" class="w3_form w3l_form_fancy">
					<fieldset class="step agileinfo w3ls_fancy_step">
						<legend>Personal Info</legend>
						<div class="abt-agile">
							<div class="abt-agile-left">
							</div>
							<div class="abt-agile-right">
								<h3><?php echo $Fname." ".$Lname; ?></h3>
								<h5><?php echo htmlspecialchars(isset($_SESSION['position']) ? $_SESSION['position'] : 'Hostel Manager', ENT_QUOTES, 'UTF-8'); ?></h5>
								<ul class="address">
									<li>
										<ul class="address-text">
											<li><b>Username </b></li>
											<li>: <?php echo $Uname; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>PHONE </b></li>
											<li>: <?php echo $Mob; ?></li>
										</ul>
									</li>
                 					<?php if ($Hostel_name !== null): ?><li>
										<ul class="address-text">
										<li><b>Managing Hostel </b></li>
											<li>: <?php echo $Hostel_name; ?></li>
										</ul>
									</li><?php endif; ?>
									<?php if ($Mess_name !== null): ?><li>
										<ul class="address-text">
										<li><b>Managing Mess </b></li>
											<li>: <?php echo $Mess_name; ?></li>
										</ul>	
									</li><?php endif; ?>
									<?php if ($Hostel_Occupancy !== null): ?><li>
										<ul class="address-text">
										<li><b>Hostel Occupancy </b></li>
											<li>: <?php echo $Hostel_Occupancy. '%'; ?></li>
										</ul>	
									</li><?php endif; ?>
									<?php if ($Mess_Occupancy !== null): ?><li>
										<ul class="address-text">
										<li><b>Mess Occupancy </b></li>
											<li>: <?php echo $Mess_Occupancy. '%'; ?></li>
										</ul>	
									</li><?php endif; ?>
								</ul>
							</div>
								<div class="clear"></div>
						</div>
				</fieldset>

        <fieldset class="step agileinfo w3ls_fancy_step">
          <legend>Admin Info</legend>
          <div class="abt-agile">
            <div class="abt-agile-left">
            </div>
            <div class="abt-agile-right">
							<?php
									$ad=1;
									$queryA = "SELECT * FROM Hostel_Manager WHERE Isadmin = '$ad'";
									$resultA = mysqli_query($conn,$queryA);
									$rowA = mysqli_fetch_assoc($resultA);
									$adFname = $rowA['Fname'];
									$adLname = $rowA['Lname'];
									$adUname = $rowA['Username'];
									$adMob = $rowA['Mob_no'];
							 ?>
              <h3><?php echo $adFname." ".$adLname; ?></h3>
              <h5>Admin</h5>
              <ul class="address">
                <li>
                  <ul class="address-text">
                    <li><b>Username </b></li>
                    <li>: <?php echo $adUname; ?></li>
                  </ul>
                </li>
                <li>
                  <ul class="address-text">
                    <li><b>PHONE </b></li>
                    <li>: <?php echo $adMob; ?></li>
                  </ul>
                </li>

              </ul>
            </div>
              <div class="clear"></div>
          </div>
      </fieldset>

				</form>
			</div>
		</div>

	</div>
	<script type="text/javascript" src="../web_profile/js/smoothbox.jquery2.js"></script>
	<script type="text/javascript" src="../web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="../web_home/js/bootstrap.js"></script> 
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
