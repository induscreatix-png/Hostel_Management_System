<?php
session_start();
 require 'includes/config.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Nayab Hostel</title>

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
	<!--// Meta tag Keywords -->

	<!-- css files -->
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all"/> <!-- Style-CSS -->
	<link rel="stylesheet" href="web/css/student-dashboard.css">
	<link rel="stylesheet" href="web/css/home-slider.css">
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> <!-- Font-Awesome-Icons-CSS -->
	<!-- //css files -->

	<!-- testimonials css -->
	<link rel="stylesheet" href="web_home/css_home/flexslider.css" type="text/css" media="screen" property="" /><!-- flexslider css -->
	<!-- //testimonials css -->

	<!-- web-fonts -->
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<!-- //web-fonts -->

</head>

<body>

<!-- banner -->
	<div class="banner" id="home">
<!--Header-->
<?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
<!--Header-->
		<section class="home-slider" aria-label="Hostel highlights" aria-live="off">
			<article class="home-slide is-active" aria-hidden="false">
				<img src="web_home/images/1.png" alt="">
				<div class="home-slide-content"><h2>Nayab Hostel</h2></div>
			</article>
			<article class="home-slide" aria-hidden="true">
				<img src="web_home/images/2.png" alt="">
				<div class="home-slide-content"><h2>Rooms</h2></div>
			</article>
			<article class="home-slide" aria-hidden="true">
				<img src="web_home/images/3.png" alt="">
				<div class="home-slide-content"><h2>Hostels</h2></div>
			</article>
		</section>
	</div>




<!-- footer -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<!-- footer -->

<!-- js-scripts -->

	<!-- js -->
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap -->
	<!-- //js -->

	<!-- banner js -->
	<script src="web_home/js/home-slider.js?v=1"></script>
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
			

			$().UItoTop({ easingType: 'easeOutQuart' });

			});
	</script>

</body>
</html>
