<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Nayab Hostel</title>
    <!-- meta tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="keywords" content="Art Sign Up Form Responsive Widget, Audio and Video players, Login Form Web Template, Flat Pricing Tables, Flat Drop-Downs, Sign-Up Web Templates,
        Flat Web Templates, Login Sign-up Responsive Web Template, Smartphone Compatible Web Template, Free Web Designs for Nokia, Samsung, LG, Sony Ericsson, Motorola Web Design"
    />
    <!-- /meta tags -->
    <!-- custom style sheet -->
    <link href="web/css/style.css?v=2" rel="stylesheet" type="text/css" />
    <!-- /custom style sheet -->
    <!-- fontawesome css -->
    <link href="web/css/fontawesome-all.css" rel="stylesheet" />
    <!-- /fontawesome css -->
    <!-- google fonts-->
    <link href="//fonts.googleapis.com/css?family=Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- /google fonts-->

</head>


<body class="auth-page">
    <?php require_once __DIR__ . '/includes/site_chrome.php'; renderSiteHeader(); ?>
    <main class="auth-main">
        <section class="auth-card" aria-labelledby="student-login-title">
            <p class="auth-kicker">Student portal</p>
            <h1 id="student-login-title">Welcome back</h1>
            <p class="auth-description">Sign in to manage your hostel applications, payments, and student profile.</p>
            <form action="includes/login.inc.php" method="post">
                <div class="auth-fields">
                    <label class="full">Student roll number
                        <input type="text" name="student_roll_no" autocomplete="username" required>
                    </label>
                    <label class="full">Password
                        <input type="password" name="pwd" autocomplete="current-password" required>
                    </label>
                </div>
                <button class="auth-submit" type="submit" name="login-submit">Sign in</button>
            </form>
            <p class="auth-alt">New to Nayab Hostel? <a href="signup.php">Create a student account</a></p>
            <p class="auth-alt">Staff member? <a href="login-hostel_manager.php">Staff sign in</a></p>
        </section>
    </main>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>

</html>
