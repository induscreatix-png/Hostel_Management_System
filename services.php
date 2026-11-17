<?php session_start(); ?>
<!--
	Author: W3layouts
	Author URL: http://w3layouts.com
	License: Creative Commons Attribution 3.0 Unported
	License URL: http://creativecommons.org/licenses/by/3.0/
-->

<?php

 require 'includes/config.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Services | Nayab Hostel</title>

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
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS -->
	<link rel="stylesheet" href="web/css/student-dashboard.css">
	<link rel="stylesheet" href="web/css/hostel-listing.css">
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
<!-- //banner -->



<main class="hostel-listing">
	<div class="hostel-list-grid">
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=A"><img src="web_home/images/s1.jpg" alt="A Hostel"></a>
			<div class="hostel-card-content"><h2>A Hostel</h2><a class="hostel-apply" href="application_form.php?id=A">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=B"><img src="web_home/images/s2.jpg" alt="B Hostel"></a>
			<div class="hostel-card-content"><h2>B Hostel</h2><a class="hostel-apply" href="application_form.php?id=B">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=C"><img src="web_home/images/s3.jpg" alt="C Hostel"></a>
			<div class="hostel-card-content"><h2>C Hostel</h2><a class="hostel-apply" href="application_form.php?id=C">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=D"><img src="web_home/images/s4.jpg" alt="D Hostel"></a>
			<div class="hostel-card-content"><h2>D Hostel</h2><a class="hostel-apply" href="application_form.php?id=D">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=E"><img src="web_home/images/s2.jpg" alt="E Hostel"></a>
			<div class="hostel-card-content"><h2>E Hostel</h2><a class="hostel-apply" href="application_form.php?id=E">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=F"><img src="web_home/images/s3.jpg" alt="F Hostel"></a>
			<div class="hostel-card-content"><h2>F Hostel</h2><a class="hostel-apply" href="application_form.php?id=F">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=G"><img src="web_home/images/s1.jpg" alt="G Hostel"></a>
			<div class="hostel-card-content"><h2>G Hostel</h2><a class="hostel-apply" href="application_form.php?id=G">Apply for hostel</a></div>
		</article>
		<article class="hostel-card">
			<a class="hostel-card-image" href="application_form.php?id=H"><img src="web_home/images/s2.jpg" alt="H Hostel"></a>
			<div class="hostel-card-content"><h2>H Hostel</h2><a class="hostel-apply" href="application_form.php?id=H">Apply for hostel</a></div>
		</article>
	</div>
</main>

<!-- team -->

<!-- //team -->

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
