<?php
function siteChromeContext() {
	if (!empty($_SESSION['isadmin']) && (int)$_SESSION['isadmin'] === 1) {
		return 'admin';
	}
	if (!empty($_SESSION['hostel_man_id'])) {
		$position = isset($_SESSION['position']) ? $_SESSION['position'] : '';
		if ($position === 'Finance Reviewer') {
			return 'finance';
		}
		if ($position === 'Mess Manager') {
			return 'mess_staff';
		}
		if ($position === 'Guard') {
			return 'guard';
		}
		return 'hostel_staff';
	}
	if (!empty($_SESSION['roll'])) {
		return 'student';
	}
	return 'public';
}

function siteChromeLinks($context) {
	if ($context === 'admin') {
		return array(
			array('Dashboard', 'admin/admin_home.php', array('admin_home.php')),
			array('Students', 'admin/students.php', array('students.php')),
			array('Room requests', 'admin/room_requests.php', array('room_requests.php')),
			array('Mess applications', 'admin/mess_requests.php', array('mess_requests.php')),
			array('Payments', 'admin/payments.php', array('payments.php', 'payment_proof.php')),
			array('Complaints', 'manage_complaints.php', array('manage_complaints.php')),
			array('Staff access', 'admin/create_hm.php', array('create_hm.php'))
		);
	}
	if ($context === 'hostel_staff') {
		return array(
			array('Home', 'home_manager.php', array('home_manager.php')),
			array('Allocate rooms', 'allocate_room.php', array('allocate_room.php')),
			array('Student requests', 'manage_student_requests.php', array('manage_student_requests.php')),
			array('Complaints', 'manage_complaints.php', array('manage_complaints.php')),
			array('Allocated rooms', 'allocated_rooms.php', array('allocated_rooms.php')),
			array('Available beds', 'empty_rooms.php', array('empty_rooms.php')),
			array('Vacate rooms', 'vacate_rooms.php', array('vacate_rooms.php')),
			array('Profile', 'admin/manager_profile.php', array('manager_profile.php'))
		);
	}
	if ($context === 'mess_staff') {
		return array(
			array('Home', 'home_manager.php', array('home_manager.php')),
			array('Allocate mess', 'allocate_mess_card.php', array('allocate_mess_card.php')),
			array('Student requests', 'manage_student_requests.php', array('manage_student_requests.php')),
			array('Allocated mess', 'allocated_mess_card.php', array('allocated_mess_card.php')),
			array('Vacate mess', 'vacate_mess.php', array('vacate_mess.php')),
			array('Profile', 'admin/manager_profile.php', array('manager_profile.php'))
		);
	}
	if ($context === 'finance') {
		return array(
			array('Payment review', 'admin/payments.php', array('payments.php', 'payment_proof.php'))
		);
	}
	if ($context === 'guard') {
		return array(
			array('Student movement', 'guard_movement.php', array('guard_movement.php'))
		);
	}
	if ($context === 'student') {
		return array(
			array('Home', 'home.php', array('home.php')),
			array('Hostels', 'services.php', array('services.php', 'application_form.php')),
			array('Mess', 'services_mess.php', array('services_mess.php', 'application_form_mess.php')),
			array('Payment', 'payment_form.php', array('payment_form.php')),
			array('Complaints', 'complaints.php', array('complaints.php')),
			array('Profile', 'profile.php', array('profile.php'))
		);
	}
	return array(
		array('Home', 'home.php', array('home.php')),
		array('Hostels', 'services.php', array('services.php')),
		array('Mess', 'services_mess.php', array('services_mess.php')),
		array('Student login', 'index.php', array('index.php')),
		array('Staff login', 'login-hostel_manager.php', array('login-hostel_manager.php')),
		array('Sign up', 'signup.php', array('signup.php'))
	);
}

function siteChromeHref($route, $isAdminDirectory) {
	if (!$isAdminDirectory) {
		return $route;
	}
	return strpos($route, 'admin/') === 0 ? substr($route, 6) : '../' . $route;
}

function renderSiteHeader() {
	$context = siteChromeContext();
	$links = siteChromeLinks($context);
	$isAdminDirectory = basename(dirname(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '')) === 'admin';
	$currentPage = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
	$brandRoute = $context === 'admin' ? 'admin/admin_home.php' : (in_array($context, array('hostel_staff', 'mess_staff'), true) ? 'home_manager.php' : ($context === 'finance' ? 'admin/payments.php' : ($context === 'guard' ? 'guard_movement.php' : 'home.php')));
	?>
	<header class="site-header">
		<div class="site-header-inner">
			<a class="site-brand" href="<?php echo htmlspecialchars(siteChromeHref($brandRoute, $isAdminDirectory), ENT_QUOTES, 'UTF-8'); ?>">Nayab<span> Hostel</span></a>
			<nav class="site-nav" aria-label="Main navigation">
				<?php foreach ($links as $link): ?>
					<a href="<?php echo htmlspecialchars(siteChromeHref($link[1], $isAdminDirectory), ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($currentPage, $link[2], true) ? ' class="active" aria-current="page"' : ''; ?>><?php echo htmlspecialchars($link[0], ENT_QUOTES, 'UTF-8'); ?></a>
				<?php endforeach; ?>
				<?php if ($context !== 'public'): ?><a href="<?php echo htmlspecialchars(siteChromeHref('includes/logout.inc.php', $isAdminDirectory), ENT_QUOTES, 'UTF-8'); ?>">Logout</a><?php endif; ?>
			</nav>
		</div>
	</header>
	<?php
}

?>
