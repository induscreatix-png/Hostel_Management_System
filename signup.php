<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Student sign up | Nayab Hostel</title>
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
        <section class="auth-card auth-card-wide" aria-labelledby="signup-title">
            <p class="auth-kicker">Student portal</p>
            <h1 id="signup-title">Create your account</h1>
            <p class="auth-description">Enter your student details to get started with hostel applications and services.</p>
            <form action="includes/signup.inc.php" method="post">
                <div class="auth-fields">
                    <label>Student roll number
                        <input type="text" name="student_roll_no" autocomplete="username" pattern="[A-Za-z0-9]+" required>
                    </label>
                    <label>Mobile number
                        <input type="tel" name="mobile_no" autocomplete="tel" required>
                    </label>
                    <label>First name
                        <input type="text" name="student_fname" autocomplete="given-name" required>
                    </label>
                    <label>Last name
                        <input type="text" name="student_lname" autocomplete="family-name" required>
                    </label>
                    <label>Department
                        <input type="text" name="department" required>
                    </label>
                    <label>Year of study
                        <input type="text" name="year_of_study" required>
                    </label>
                    <label>Password
                        <input type="password" name="pwd" autocomplete="new-password" minlength="8" required>
                    </label>
                    <label>Confirm password
                        <input type="password" name="confirmpwd" autocomplete="new-password" minlength="8" required>
                    </label>
                </div>
                <button class="auth-submit" type="submit" name="signup-submit">Create account</button>
            </form>
            <p class="auth-alt">Already registered? <a href="index.php">Sign in</a></p>
        </section>
    </main>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>

</html>
